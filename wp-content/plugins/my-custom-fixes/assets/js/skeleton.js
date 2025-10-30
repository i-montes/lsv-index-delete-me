(function () {
  function markLoaded(img) {
    img.setAttribute("data-loaded", "true");
  }

  function bindSkeleton(img) {
    if (img.dataset.skeletonBound) {
      return;
    }

    img.dataset.skeletonBound = "true";

    if (img.complete && img.naturalWidth > 0) {
      markLoaded(img);
      return;
    }

    img.addEventListener(
      "load",
      function () {
        markLoaded(img);
      },
      { once: true }
    );

    img.addEventListener(
      "error",
      function () {
        markLoaded(img);
      },
      { once: true }
    );
  }

  function initSkeletons() {
    var nodes = document.querySelectorAll("img[data-skeleton]");
    nodes.forEach(function (img) {
      bindSkeleton(img);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSkeletons);
  } else {
    initSkeletons();
  }
})();
