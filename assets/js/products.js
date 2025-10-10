function showToast(message, type = "success") {
  const toastContainer = document.querySelector(".toast-container");
  const toast = document.createElement("div");
  toast.className = `custom-toast ${type}`;
  toast.textContent = message;
  toastContainer.appendChild(toast);

  setTimeout(() => toast.classList.add("show"), 100);

  // Disparition après 5 secondes
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 5000);
}

// Fonction pour vérifier si on vient d'ajouter une dépense
function checkForNewExpense() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("success") === "expense") {
    showToast("La dépense a été ajoutée avec succès !");
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

$(document).ready(function () {
  checkForNewExpense();
  $("#productsTable").DataTable({
    language: {
      url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json",
    },
    dom:
      "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    order: [[0, "desc"]],
    pageLength: 25,
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Tous"],
    ],
    columnDefs: [
      {
        orderable: true,
        targets: "_all",
      },
      {
        searchable: false,
        targets: [0, 2, 3, 4, 5, 6, 7, 8],
      },
    ],
  });

  // Initialisation des tableaux de dépenses
  $(".expenses-table").each(function () {
    $(this).DataTable({
      language: {
        url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json",
      },
      dom:
        "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
        "<'row'<'col-sm-12'tr>>" +
        "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
      pageLength: 10,
      ordering: true,
      order: [[0, "desc"]],
      columnDefs: [
        {
          type: "date",
          targets: 0,
        },
      ],
    });
  });

  $(".modal").on("hidden.bs.modal", function () {
    $(this).find(".expenses-table").DataTable().search("").draw();
  });
});
