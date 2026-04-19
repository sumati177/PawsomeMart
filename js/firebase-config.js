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
  const creds = window.FIREBASE_USER_CREDS;
  let user = auth.currentUser;

  // 1. Wait for initial auth state if not ready
  if (!user) {
    user = await waitForAuth();
  }

  // 2. If PHP provides credentials, ensure the JS SDK matches that user
  if (creds && creds.email && creds.password) {
    if (!user || user.email !== creds.email) {
      console.log("[Auth] ID mismatch. Forcing sign-in for:", creds.email);
      try {
        const result = await signInWithEmailAndPassword(auth, creds.email, creds.password);
        console.log("[Auth] Forced sign-in success:", result.user.uid);
        return result.user;
      } catch (e) {
        console.error("[Auth] Forced sign-in failed:", e.code, e.message);
        // Fall through to return whatever user we had
      }
    }
  }

  if (user) {
    console.log("[Auth] Active session:", user.email, "(" + user.uid + ")");
  } else {
    console.warn("[Auth] No active session found.");
  }
  
  return user;
}

export { db, auth, ensureAuth, waitForAuth, signOut, onAuthStateChanged };
