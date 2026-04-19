import { initializeApp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";
import { getAuth, signInWithEmailAndPassword, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-auth.js";

const firebaseConfig = {
  apiKey: window.FIREBASE_CONFIG.apiKey,
  projectId: window.FIREBASE_CONFIG.projectId,
  authDomain: window.FIREBASE_CONFIG.projectId + ".firebaseapp.com",
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

/**
 * Returns a promise that resolves with the current Firebase Auth user,
 * or null if not signed in. Waits for at most 5 seconds for auth to initialize.
 */
function waitForAuth() {
  return new Promise((resolve) => {
    const unsubscribe = onAuthStateChanged(auth, (user) => {
      unsubscribe();
      resolve(user);
    });
    // Safety timeout — don't block forever
    setTimeout(() => resolve(null), 5000);
  });
}

/**
 * Ensure the client-side Firebase Auth has a signed-in user.
 * Uses credentials stored in the page by PHP after server-side login.
 * Returns the Firebase user, or null on failure.
 */
async function ensureAuth() {
  // Check if already signed in
  let user = auth.currentUser;
  if (user) return user;

  // Wait for auth initialization
  user = await waitForAuth();
  if (user) return user;

  // Try to sign in with credentials embedded in the page
  const creds = window.FIREBASE_USER_CREDS;
  if (creds && creds.email && creds.password) {
    try {
      const result = await signInWithEmailAndPassword(auth, creds.email, creds.password);
      console.log("[Auth] Signed in as:", result.user.uid);
      return result.user;
    } catch (e) {
      console.error("[Auth] Sign-in failed:", e.code, e.message);
      return null;
    }
  }

  console.warn("[Auth] No credentials available for client-side sign-in");
  return null;
}

export { db, auth, ensureAuth, waitForAuth, signOut, onAuthStateChanged };
