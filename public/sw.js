const CACHE_NAME = "event-management-v1";
const urlsToCache = [
  "/",
  "/assets/css/styles.css",
  "/assets/js/scripts.js",
  "https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css",
];

// Install Service Worker
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(urlsToCache);
    })
  );
});

// Fetch Event
self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      // Cache hit - return response
      if (response) {
        return response;
      }
      return fetch(event.request);
    })
  );
});

// Push Event
self.addEventListener("push", (event) => {
  const options = {
    body: event.data
      ? event.data.text()
      : "New notification from Event Management",
    icon: "/assets/img/logo.png",
    badge: "/assets/img/badge.png",
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1,
    },
    actions: [
      {
        action: "explore",
        title: "Explore this new event",
        icon: "/assets/img/checkmark.png",
      },
      {
        action: "close",
        title: "Close notification",
        icon: "/assets/img/xmark.png",
      },
    ],
  };

  event.waitUntil(
    self.registration.showNotification("Event Management", options)
  );
});

// Notification Click Event
self.addEventListener("notificationclick", (event) => {
  event.notification.close();

  if (event.action === "explore") {
    // Open the app to events page
    event.waitUntil(clients.openWindow("/events.php"));
  } else if (event.action === "close") {
    // Just close the notification
    event.notification.close();
  } else {
    // Default action - open the app
    event.waitUntil(clients.openWindow("/"));
  }
});

// Background Sync untuk offline support
self.addEventListener("sync", (event) => {
  if (event.tag === "background-sync") {
    event.waitUntil(doBackgroundSync());
  }
});

function doBackgroundSync() {
  // Sync pending notifications when back online
  return fetch("/api/sync-notifications")
    .then((response) => {
      if (response.ok) {
        console.log("Background sync completed");
      }
    })
    .catch((error) => {
      console.error("Background sync failed:", error);
    });
}
