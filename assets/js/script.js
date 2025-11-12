// assets/js/script.js
// Custom JavaScript for Booking Ruangan UCA

document.addEventListener("DOMContentLoaded", function () {
  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Check availability form
  const bookingForm = document.getElementById("bookingForm");
  if (bookingForm) {
    const ruanganSelect = bookingForm.querySelector('[name="ruangan_id"]');
    const tanggalInput = bookingForm.querySelector('[name="tanggal_booking"]');
    const jamMulaiInput = bookingForm.querySelector('[name="jam_mulai"]');
    const jamSelesaiInput = bookingForm.querySelector('[name="jam_selesai"]');

    // Set min date to today
    if (tanggalInput) {
      const today = new Date().toISOString().split("T")[0];
      tanggalInput.setAttribute("min", today);

      // Set max date
      const maxDate = new Date();
      maxDate.setDate(maxDate.getDate() + 30);
      tanggalInput.setAttribute("max", maxDate.toISOString().split("T")[0]);
    }

    // Real-time availability check
    function checkAvailability() {
      if (
        !ruanganSelect.value ||
        !tanggalInput.value ||
        !jamMulaiInput.value ||
        !jamSelesaiInput.value
      ) {
        return;
      }

      const formData = new FormData();
      formData.append("ruangan_id", ruanganSelect.value);
      formData.append("tanggal", tanggalInput.value);
      formData.append("jam_mulai", jamMulaiInput.value);
      formData.append("jam_selesai", jamSelesaiInput.value);

      fetch("../api/check_availability.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          const alertDiv = document.getElementById("availabilityAlert");
          if (alertDiv) {
            if (data.success) {
              alertDiv.className = "alert alert-success";
              alertDiv.innerHTML =
                '<i class="fas fa-check-circle me-2"></i>' + data.message;
            } else {
              alertDiv.className = "alert alert-warning";
              alertDiv.innerHTML =
                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                data.message;
            }
            alertDiv.style.display = "block";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
        });
    }

    // Add event listeners
    if (ruanganSelect)
      ruanganSelect.addEventListener("change", checkAvailability);
    if (tanggalInput)
      tanggalInput.addEventListener("change", checkAvailability);
    if (jamMulaiInput)
      jamMulaiInput.addEventListener("change", checkAvailability);
    if (jamSelesaiInput)
      jamSelesaiInput.addEventListener("change", checkAvailability);
  }

  // Confirm delete
  const deleteButtons = document.querySelectorAll(".btn-delete");
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!confirm("Apakah Anda yakin ingin menghapus data ini?")) {
        e.preventDefault();
      }
    });
  });

  // Image preview
  const imageInputs = document.querySelectorAll(
    'input[type="file"][accept*="image"]'
  );
  imageInputs.forEach((input) => {
    input.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.getElementById(input.id + "_preview");
          if (preview) {
            preview.src = e.target.result;
            preview.style.display = "block";
          }
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // Data tables initialization (if DataTables is included)
  if (typeof $.fn.DataTable !== "undefined") {
    $(".data-table").DataTable({
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
      },
      pageLength: 10,
      ordering: true,
      searching: true,
    });
  }

  // Tooltips
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Print button
  const printButtons = document.querySelectorAll(".btn-print");
  printButtons.forEach((button) => {
    button.addEventListener("click", function () {
      window.print();
    });
  });
});

// Utility Functions
function formatRupiah(angka) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(angka);
}

function formatDate(dateString) {
  const options = { year: "numeric", month: "long", day: "numeric" };
  return new Date(dateString).toLocaleDateString("id-ID", options);
}

function showLoading() {
  const loadingDiv = document.createElement("div");
  loadingDiv.id = "loadingOverlay";
  loadingDiv.className = "loading-overlay";
  loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
  document.body.appendChild(loadingDiv);
}

function hideLoading() {
  const loadingDiv = document.getElementById("loadingOverlay");
  if (loadingDiv) {
    loadingDiv.remove();
  }
}
