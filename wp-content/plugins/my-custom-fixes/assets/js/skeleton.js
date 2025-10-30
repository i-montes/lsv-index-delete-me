(function () {
  function markLoaded(img) {
    img.setAttribute("data-loaded", "true");
  }

  function bindSkeleton(img) {
    if (img.dataset.skeletonBound) {
      return;
    }

    img.dataset.skeletonBound = "true";

    var width = img.getAttribute("width");
    var height = img.getAttribute("height");

    if (width && height) {
      if (!img.style.aspectRatio) {
        img.style.aspectRatio = width + " / " + height;
      }

      if (!img.style.height || img.style.height === "100%") {
        img.style.height = "auto";
      }

      if (!img.style.width) {
        img.style.width = "100%";
      }
    }

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
