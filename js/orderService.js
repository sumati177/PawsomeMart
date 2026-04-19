import { db } from './firebase-config.js';
import { collection, doc, addDoc, updateDoc, onSnapshot, query, where, orderBy, getDocs, runTransaction, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

class OrderService {
    async placeOrder(userId, userEmail, items, totalAmount, address, phone) {
        try {
            // Transaction for atomic stock update
            await runTransaction(db, async (transaction) => {
                const productRefs = items.map(item => ({
                    ref: doc(db, "products", item.productId),
                    item: item
                }));

                const productSnaps = await Promise.all(productRefs.map(p => transaction.get(p.ref)));

                // Check stock for all items
                productSnaps.forEach((snap, idx) => {
                    if (!snap.exists()) {
                        throw new Error(`Product ${productRefs[idx].item.name} not found`);
                    }
                    const currentStock = snap.data().stock || 0;
                    if (currentStock < productRefs[idx].item.quantity) {
                        throw new Error(`Not enough stock for ${productRefs[idx].item.name}. Only ${currentStock} left.`);
                    }
                });

                // Stock is adequate, decrement all
                productSnaps.forEach((snap, idx) => {
                    const currentStock = snap.data().stock || 0;
                    transaction.update(productRefs[idx].ref, {
                        stock: currentStock - productRefs[idx].item.quantity
                    });
                });
            });

            // If transaction successful, place the order
            const orderData = {
                userId,
                userEmail,
                items,
                totalAmount,
                address,
                phone,
                status: "pending",
                createdAt: serverTimestamp()
            };

            const docRef = await addDoc(collection(db, "orders"), orderData);
            await updateDoc(docRef, { orderId: docRef.id });

            return { success: true, orderId: docRef.id };
        } catch (error) {
            console.error("Order error:", error);
            return { success: false, error: error.message || error };
        }
    }

    subscribeToAdminOrders(callback) {
        const q = query(collection(db, "orders"), orderBy("createdAt", "desc"));
        return onSnapshot(q, (snapshot) => {
            const orders = snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
            callback(orders);
        });
    }

    async getUserOrders(userId) {
        const q = query(
            collection(db, "orders"),
            where("userId", "==", userId),
            orderBy("createdAt", "desc")
        );
        const snapshot = await getDocs(q);
        return snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    }

    async updateOrderStatus(orderId, newStatus) {
        try {
            const orderRef = doc(db, "orders", orderId);
            await updateDoc(orderRef, { status: newStatus });
            return { success: true };
        } catch (error) {
            return { success: false, error: error.message };
        }
    }
}

export const orderService = new OrderService();
