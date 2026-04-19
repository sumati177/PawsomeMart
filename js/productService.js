import { db } from './firebase-config.js';
import { collection, doc, getDoc, getDocs, updateDoc } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

class ProductService {
    async getProduct(productId) {
        const productRef = doc(db, "products", productId);
        const snap = await getDoc(productRef);
        if (snap.exists()) {
            return { id: snap.id, ...snap.data() };
        }
        return null;
    }

    async checkStock(productId, quantity) {
        const product = await this.getProduct(productId);
        if (!product) throw new Error("Product not found");
        return product.stock >= quantity;
    }
}

export const productService = new ProductService();
