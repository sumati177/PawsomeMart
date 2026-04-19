import { db } from './firebase-config.js';
import { collection, doc, addDoc, updateDoc, setDoc, getDocs, query, where, orderBy, runTransaction, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

class OrderService {
    async placeOrder(userId, userEmail, items, totalAmount, address, phone) {
        console.log("Placing order...");
        console.log("User:", userId);
        console.log("Cart:", items);
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

            // Step 9: Save user info
            await setDoc(doc(db, "users", userId), {
                phone,
                address
            }, { merge: true });

            const orderData = {
                userId: userId,
                items: items,
                totalAmount: totalAmount,
                address: address,
                phone: phone,
                status: "pending",
                createdAt: serverTimestamp()
            };

            const docRef = await addDoc(collection(db, "orders"), orderData);
            console.log("Order saved with ID:", docRef.id);

            if (!docRef.id) {
                throw new Error("Order document not created!");
            }

            return { success: true, orderId: docRef.id };
        } catch (error) {
            console.error("Order error:", error);
            return { success: false, error: error.message || error };
        }
    }

    async getAdminOrders() {
        console.log("Fetching ALL orders (admin)");
        const snapshot = await getDocs(collection(db, "orders"));
        console.log("Admin orders retrieved:", snapshot.docs.length);
        return snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    }

    async getUserOrders(userId) {
        console.log("Fetching user orders...");
        console.log("UID:", userId);
        const q = query(
            collection(db, "orders"),
            where("userId", "==", userId)
        );
        const snapshot = await getDocs(q);
        console.log("Orders fetched:", snapshot.docs.length);
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
