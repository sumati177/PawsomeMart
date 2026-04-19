import { db, auth, ensureAuth } from './firebase-config.js';
import { collection, doc, addDoc, updateDoc, setDoc, getDocs, query, where, runTransaction, serverTimestamp, getDoc } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

class OrderService {

    /**
     * Place an order as the currently authenticated Firebase user.
     * Complies with strict rules: userId MUST equal auth.currentUser.uid
     */
    async placeOrder(items, totalAmount, address, phone, paymentMethod) {
        console.log("[OrderService] placeOrder() called");

        // Step 1: Ensure we have an authenticated Firebase user
        const user = await ensureAuth();
        if (!user) {
            return { success: false, error: "You must be logged in. Please refresh and try again." };
        }

        const userId = user.uid;
        console.log("[OrderService] Authenticated as:", userId);
        console.log("[OrderService] Cart items:", items);

        try {
            // Step 2: Validate stock via transaction (products are public-read)
            await runTransaction(db, async (transaction) => {
                const productRefs = items.map(item => ({
                    ref: doc(db, "products", item.productId),
                    item: item
                }));

                const productSnaps = await Promise.all(
                    productRefs.map(p => transaction.get(p.ref))
                );

                // Check stock for all items
                productSnaps.forEach((snap, idx) => {
                    if (!snap.exists()) {
                        throw new Error(`Product "${productRefs[idx].item.name}" not found in database.`);
                    }
                    const currentStock = snap.data().stock || 0;
                    if (currentStock < productRefs[idx].item.quantity) {
                        throw new Error(`Not enough stock for "${productRefs[idx].item.name}". Only ${currentStock} left.`);
                    }
                });

                // Decrement stock
                productSnaps.forEach((snap, idx) => {
                    const currentStock = snap.data().stock || 0;
                    transaction.update(productRefs[idx].ref, {
                        stock: currentStock - productRefs[idx].item.quantity
                    });
                });
            });

            console.log("[OrderService] Stock validation passed");

            // Step 3: Save user address/phone to their own document
            // (rules: users can write their own doc where uid == userId)
            try {
                await setDoc(doc(db, "users", userId), {
                    phone: phone,
                    address: address
                }, { merge: true });
            } catch (profileErr) {
                console.warn("[OrderService] Could not update user profile:", profileErr.message);
                // Non-fatal — continue with order
            }

            // Step 4: Create the order document
            // CRITICAL: userId MUST be auth.currentUser.uid for rules to pass
            const orderData = {
                userId: userId,
                userEmail: user.email || "",
                items: items,
                totalAmount: totalAmount,
                address: address,
                phone: phone,
                paymentMethod: paymentMethod || "COD",
                status: "placed",
                createdAt: serverTimestamp()
            };

            console.log("[OrderService] Writing order to Firestore...");
            const docRef = await addDoc(collection(db, "orders"), orderData);
            console.log("[OrderService] ✅ Order saved with ID:", docRef.id);

            return { success: true, orderId: docRef.id };

        } catch (error) {
            console.error("[OrderService] ❌ Order failed:", error);

            // Provide user-friendly error messages
            if (error.code === "permission-denied") {
                return { success: false, error: "Permission denied. Please log in again." };
            }
            return { success: false, error: error.message || "Unknown error" };
        }
    }

    /**
     * Fetch orders for the currently authenticated user.
     * Rules: users can only read orders where userId == auth.uid
     */
    async getUserOrders() {
        console.log("[OrderService] getUserOrders() called");

        const user = await ensureAuth();
        if (!user) {
            console.error("[OrderService] Not authenticated — cannot fetch user orders");
            return [];
        }

        const userId = user.uid;
        console.log("[OrderService] Fetching orders for UID:", userId);

        try {
            const q = query(
                collection(db, "orders"),
                where("userId", "==", userId)
            );
            const snapshot = await getDocs(q);
            const orders = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
            console.log("[OrderService] Orders fetched:", orders.length);
            return orders;
        } catch (error) {
            console.error("[OrderService] getUserOrders error:", error);
            if (error.code === "permission-denied") {
                throw new Error("Access denied. Your session may have expired.");
            }
            throw error;
        }
    }

    /**
     * Fetch ALL orders (admin only).
     * Rules require: user must be authenticated AND have role=="admin" in users/{uid}
     */
    async getAdminOrders() {
        console.log("[OrderService] getAdminOrders() called");

        const user = await ensureAuth();
        if (!user) {
            throw new Error("Admin authentication required.");
        }

        console.log("[OrderService] Admin UID:", user.uid);

        try {
            // First verify this user is actually admin
            const userDoc = await getDoc(doc(db, "users", user.uid));
            if (!userDoc.exists()) {
                throw new Error("User profile not found.");
            }
            const profile = userDoc.data();
            if (profile.isAdmin !== true && profile.role !== "admin") {
                throw new Error("You do not have admin privileges.");
            }

            // Fetch all orders
            const snapshot = await getDocs(collection(db, "orders"));
            const orders = snapshot.docs.map(d => ({ id: d.id, ...d.data() }));
            console.log("[OrderService] Admin orders retrieved:", orders.length);
            return orders;
        } catch (error) {
            console.error("[OrderService] getAdminOrders error:", error);
            if (error.code === "permission-denied") {
                throw new Error("Permission denied. Firestore rules may be blocking admin access.");
            }
            throw error;
        }
    }

    /**
     * Update order status (admin only)
     */
    async updateOrderStatus(orderId, newStatus) {
        console.log("[OrderService] updateOrderStatus:", orderId, "→", newStatus);

        const user = await ensureAuth();
        if (!user) {
            return { success: false, error: "Authentication required." };
        }

        try {
            const orderRef = doc(db, "orders", orderId);
            await updateDoc(orderRef, { status: newStatus });
            console.log("[OrderService] ✅ Status updated");
            return { success: true };
        } catch (error) {
            console.error("[OrderService] updateOrderStatus error:", error);
            if (error.code === "permission-denied") {
                return { success: false, error: "Permission denied. Only admins can update order status." };
            }
            return { success: false, error: error.message };
        }
    }
}

export const orderService = new OrderService();
