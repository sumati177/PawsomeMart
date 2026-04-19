import { initializeApp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

const firebaseConfig = {
  apiKey: window.FIREBASE_CONFIG.apiKey,
  projectId: window.FIREBASE_CONFIG.projectId,
  authDomain: window.FIREBASE_CONFIG.projectId + ".firebaseapp.com",
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

export { db };
