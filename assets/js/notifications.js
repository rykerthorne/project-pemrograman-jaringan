/**
 * assets/js/notifications.js
 * Real-time notification checker and handler
 */

class NotificationManager {
  constructor() {
    this.checkInterval = 30000; // Check every 30 seconds
    this.lastCheck = null;
    this.unreadCount = 0;
    this.notificationSound = null;
    this.init();
  }

  init() {
    // Check immediately on load
    this.checkNotifications();

    // Set up periodic checking
    setInterval(() => {
      this.checkNotifications();
    }, this.checkInterval);

    // Load notification sound (optional)
    this.notificationSound = new Audio("/assets/sounds/notification.mp3");

    // Listen for visibility change to check when tab becomes active
    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        this.checkNotifications();
      }
    });
  }

  async checkNotifications() {
    try {
      const response = await fetch("/api/notifikasi.php?action=count");
      const data = await response.json();

      if (data.success) {
        const newCount = data.unread_count;

        // If count increased, show notification
        if (newCount > this.unreadCount && this.unreadCount > 0) {
          this.showBrowserNotification();
          this.playSound();
        }

        this.unreadCount = newCount;
        this.updateBadges(newCount);
      }
    } catch (error) {
      console.error("Error checking notifications:", error);
    }
  }

  updateBadges(count) {
    // Update all notification badges on page
    const badges = document.querySelectorAll(
      ".notif-count, #notifCountBadge, #sidebarNotifCount"
    );

    badges.forEach((badge) => {
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = "block";
      } else {
        badge.style.display = "none";
      }
    });

    // Update page title with count
    const baseTitle = document.title.split("(")[0].trim();
    if (count > 0) {
      document.title = `(${count}) ${baseTitle}`;
    } else {
      document.title = baseTitle;
    }

    // Update favicon with badge (optional)
    this.updateFavicon(count);
  }

  updateFavicon(count) {
    if (count === 0) return;

    const canvas = document.createElement("canvas");
    canvas.width = 32;
    canvas.height = 32;

    const ctx = canvas.getContext("2d");

    // Draw red circle
    ctx.fillStyle = "#dc3545";
    ctx.beginPath();
    ctx.arc(24, 8, 8, 0, 2 * Math.PI);
    ctx.fill();

    // Draw count
    ctx.fillStyle = "white";
    ctx.font = "bold 12px Arial";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(count > 9 ? "9+" : count, 24, 8);

    // Update favicon
    const link =
      document.querySelector("link[rel*='icon']") ||
      document.createElement("link");
    link.type = "image/x-icon";
    link.rel = "shortcut icon";
    link.href = canvas.toDataURL();
    document.getElementsByTagName("head")[0].appendChild(link);
  }

  showBrowserNotification() {
    // Request permission if needed
    if ("Notification" in window && Notification.permission === "granted") {
      const notification = new Notification("Notifikasi Baru", {
        body: "Anda memiliki notifikasi baru dari Booking UCA",
        icon: "/assets/images/logo.png",
        badge: "/assets/images/logo.png",
        tag: "booking-notification",
      });

      notification.onclick = function () {
        window.focus();
        window.location.href = "/user/notifikasi.php";
      };
    } else if (
      "Notification" in window &&
      Notification.permission !== "denied"
    ) {
      Notification.requestPermission();
    }
  }

  playSound() {
    if (this.notificationSound) {
      this.notificationSound.play().catch((e) => {
        console.log("Cannot play notification sound:", e);
      });
    }
  }

  async markAsRead(notificationId) {
    try {
      const formData = new FormData();
      formData.append("id", notificationId);

      const response = await fetch("/api/notifikasi.php?action=read", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        this.checkNotifications();
        return true;
      }

      return false;
    } catch (error) {
      console.error("Error marking notification as read:", error);
      return false;
    }
  }

  async markAllAsRead() {
    try {
      const response = await fetch("/api/notifikasi.php?action=read_all", {
        method: "POST",
      });

      const data = await response.json();

      if (data.success) {
        this.checkNotifications();
        return true;
      }

      return false;
    } catch (error) {
      console.error("Error marking all notifications as read:", error);
      return false;
    }
  }

  async deleteNotification(notificationId) {
    try {
      const formData = new FormData();
      formData.append("id", notificationId);

      const response = await fetch("/api/notifikasi.php?action=delete", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        this.checkNotifications();
        return true;
      }

      return false;
    } catch (error) {
      console.error("Error deleting notification:", error);
      return false;
    }
  }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize if user is logged in (check if notification elements exist)
  if (
    document.querySelector(".notif-btn") ||
    document.querySelector("#notifCountBadge")
  ) {
    window.notificationManager = new NotificationManager();

    // Request browser notification permission
    if ("Notification" in window && Notification.permission === "default") {
      Notification.requestPermission();
    }
  }
});

// Helper function for toast notifications
function showToast(message, type = "info") {
  // Create toast container if not exists
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
    document.body.appendChild(toastContainer);
  }

  // Create toast
  const toast = document.createElement("div");
  toast.className = `alert alert-${type} alert-dismissible fade show`;
  toast.style.cssText = `
        min-width: 300px;
        margin-bottom: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
    `;
  toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  toastContainer.appendChild(toast);

  // Auto remove after 5 seconds
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// Add CSS animation
const style = document.createElement("style");
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
