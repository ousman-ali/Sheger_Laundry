import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Real-time: Laravel Echo + Pusher (public config only; no secrets)
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Prefer config injected by Blade (window.ECHO_CONFIG) then fall back to Vite envs
const injected = window.ECHO_CONFIG || {};
const useHost =
    import.meta.env.VITE_PUSHER_HOST || injected.wsHost ? true : false;
const cfg = {
    broadcaster: "pusher",
    key: injected.key || import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: injected.cluster || import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS:
        typeof injected.forceTLS === "boolean"
            ? injected.forceTLS
            : (import.meta.env.VITE_PUSHER_SCHEME || "https") === "https",
    ...(useHost
        ? {
              wsHost: injected.wsHost || import.meta.env.VITE_PUSHER_HOST,
              wsPort:
                  injected.wsPort ??
                  (import.meta.env.VITE_PUSHER_PORT
                      ? Number(import.meta.env.VITE_PUSHER_PORT)
                      : undefined),
              wssPort:
                  injected.wssPort ??
                  (import.meta.env.VITE_PUSHER_PORT
                      ? Number(import.meta.env.VITE_PUSHER_PORT)
                      : undefined),
              enabledTransports: ["ws", "wss"],
          }
        : {}),
};

try {
    if (cfg.key) {
        window.Echo = new Echo(cfg);
    }
} catch (e) {
    // Non-fatal; app works without realtime
    console.warn("Echo initialization failed:", e);
}
