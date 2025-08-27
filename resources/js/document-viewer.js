// resources/js/document-viewer.js
import * as pdfjsLib from "pdfjs-dist/build/pdf";

const LOCAL_WORKER = "/vendor/pdfjs/pdf.worker.min.js";
const CDN_WORKER =
  "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

async function ensureWorker() {
  try {
    const r = await fetch(LOCAL_WORKER, { method: "HEAD" });
    if (r.ok) {
      pdfjsLib.GlobalWorkerOptions.workerSrc = LOCAL_WORKER;
      return LOCAL_WORKER;
    }
  } catch (e) {}
  pdfjsLib.GlobalWorkerOptions.workerSrc = CDN_WORKER;
  return CDN_WORKER;
}

(function () {
  if (!window.DOC) {
    console.warn(
      "document-viewer: window.DOC not found — viewer will not initialize."
    );
    return;
  }

  const PREVIEW_URL = window.DOC.previewUrl;
  const DOCUMENT_ID = window.DOC.id;
  const CSRF = window.DOC.csrf;
  const CAN_CREATE = !!window.DOC.canCreateMinute;
  const MINUTES = window.DOC.minutes || [];

  const container = document.getElementById("pdf-container");
  const loadingEl = document.getElementById("pdf-loading");
  const scrollContainer = document.getElementById("pdf-scroll-container");

  if (!container) {
    console.warn("document-viewer: #pdf-container not found.");
    return;
  }

  function q(selector, root = document) {
    return root.querySelector(selector);
  }

  function escapeHtml(s) {
    if (s == null) return "";
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function createPinElement(minute) {
    const pin = document.createElement("div");
    pin.className = "doc-pin";
    pin.dataset.minuteId = minute.id;
    pin.style.position = "absolute";
    pin.style.left = `${(parseFloat(minute.pos_x || 0) * 100).toFixed(6)}%`;
    pin.style.top = `${(parseFloat(minute.pos_y || 0) * 100).toFixed(6)}%`;
    // Bottom-center anchor so the note sits above the anchor point (same as viewer UX)
    pin.style.transform = "translate(-50%, -100%)";
    pin.style.zIndex = "40";
    pin.style.cursor = "grab";
    pin.style.userSelect = "none";

    // If this minute has an attachment_url (handwritten image), render that image
    const box = minute.box_style || null;
    const imgUrl = minute.attachment_url || null;

    // wrapper container where we will append image or small card
    const wrapper = document.createElement("div");
    wrapper.style.display = "inline-block";
    wrapper.style.padding = "2px";
    wrapper.style.borderRadius = "6px";
    wrapper.style.background = "transparent";

    if (imgUrl && box && typeof box.w !== "undefined") {
      // Show image scaled to stored normalized width/height relative to overlay
      const img = document.createElement("img");
      img.src = imgUrl;
      img.alt = "annotation";
      img.style.display = "block";
      // width as percentage of overlay (we'll set CSS percent)
      img.style.width = parseFloat(box.w) * 100 + "%";
      // preserve aspect ratio by leaving height auto if box.h not provided
      if (box.h) {
        img.style.height = parseFloat(box.h) * 100 + "%";
      } else {
        img.style.height = "auto";
      }
      img.style.borderRadius = "4px";
      img.style.boxShadow = "0 2px 6px rgba(0,0,0,0.08)";
      wrapper.appendChild(img);

      // small caption with author / time
      const meta = document.createElement("div");
      meta.style.fontSize = "11px";
      meta.style.color = "#374151";
      meta.style.marginTop = "4px";
      const creator = minute.creator?.name || "Unknown";
      const createdAt = minute.created_at || "";
      meta.innerHTML = `<div style="font-weight:600">${escapeHtml(
        creator
      )}</div><div style="font-size:11px;color:#6b7280">${escapeHtml(
        createdAt
      )}</div>`;
      wrapper.appendChild(meta);
    } else {
      // fallback small text card for typed notes
      const card = document.createElement("div");
      card.style.background = "#FFF9C4";
      card.style.border = "1px solid #D6B94D";
      card.style.padding = "6px 8px";
      card.style.borderRadius = "6px";
      card.style.boxShadow = "0 2px 6px rgba(0,0,0,0.08)";
      card.style.maxWidth = "260px";
      card.style.fontSize = "12px";
      card.style.lineHeight = "1.15";
      const creator = minute.creator?.name || "Unknown";
      const createdAt = minute.created_at || "";
      const bodyPreview = minute.body
        ? minute.body.length > 200
          ? minute.body.slice(0, 200) + "…"
          : minute.body
        : "Handwritten note";
      card.innerHTML = `
        <div style="font-weight:600; font-size:12px; margin-bottom:4px;">${escapeHtml(
          creator
        )}</div>
        <div style="font-size:11px; color:#374151; margin-bottom:6px;">${escapeHtml(
          createdAt
        )}</div>
        <div style="font-size:12px; color:#111827;">${escapeHtml(
          bodyPreview
        )}</div>
      `;
      wrapper.appendChild(card);
    }

    pin.appendChild(wrapper);

    // Optional: clicking pin scrolls to a corresponding DOM minute list if exists
    pin.addEventListener("click", (e) => {
      e.stopPropagation();
      const el = document.getElementById(`minute-${minute.id}`);
      if (el) {
        el.scrollIntoView({ behavior: "smooth", block: "center" });
        el.classList.add("ring-4", "ring-yellow-200");
        setTimeout(
          () => el.classList.remove("ring-4", "ring-yellow-200"),
          1400
        );
      }
    });

    return pin;
  }

  function makePinDraggable(pinEl, minute) {
    let dragging = false;
    let pointerId = null;
    pinEl.addEventListener("dragstart", (ev) => ev.preventDefault());

    pinEl.addEventListener(
      "pointerdown",
      (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        dragging = true;
        pointerId = ev.pointerId;
        try {
          pinEl.setPointerCapture(pointerId);
        } catch (e) {}
        pinEl.style.cursor = "grabbing";
        pinEl.classList.add("opacity-90");
      },
      { passive: false }
    );

    const overlay = pinEl.closest(".page-overlay");

    async function persistPosition(xNorm, yNorm) {
      try {
        const res = await fetch(`/minutes/${minute.id}`, {
          method: "PUT",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": CSRF,
            Accept: "application/json",
          },
          body: JSON.stringify({
            pos_x: parseFloat(xNorm),
            pos_y: parseFloat(yNorm),
            page_number: minute.page_number ?? 1,
          }),
        });

        if (!res.ok) {
          const json = await res.json().catch(() => ({}));
          console.error("Failed to persist minute position", res.status, json);
          pinEl.style.boxShadow = "0 0 0 3px rgba(239,68,68,0.14)";
          setTimeout(() => (pinEl.style.boxShadow = ""), 900);
        } else {
          pinEl.style.boxShadow = "0 0 0 3px rgba(34,197,94,0.18)";
          setTimeout(() => (pinEl.style.boxShadow = ""), 700);
        }
      } catch (err) {
        console.error("Network error while saving minute position", err);
        pinEl.style.boxShadow = "0 0 0 3px rgba(239,68,68,0.14)";
        setTimeout(() => (pinEl.style.boxShadow = ""), 900);
      }
    }

    function onPointerMove(ev) {
      if (!dragging) return;
      const rect = overlay.getBoundingClientRect();
      let x = (ev.clientX - rect.left) / rect.width;
      let y = (ev.clientY - rect.top) / rect.height;
      x = Math.max(0, Math.min(1, x));
      y = Math.max(0, Math.min(1, y));
      pinEl.style.left = `${(x * 100).toFixed(6)}%`;
      pinEl.style.top = `${(y * 100).toFixed(6)}%`;
      pinEl.dataset._posX = x;
      pinEl.dataset._posY = y;
    }

    async function onPointerUp(ev) {
      if (!dragging) return;
      dragging = false;
      try {
        pinEl.releasePointerCapture(pointerId);
      } catch (e) {}
      pointerId = null;
      pinEl.style.cursor = "grab";
      pinEl.classList.remove("opacity-90");

      const x = pinEl.dataset._posX;
      const y = pinEl.dataset._posY;
      delete pinEl.dataset._posX;
      delete pinEl.dataset._posY;
      if (x == null || y == null) return;

      await persistPosition(x, y);
    }

    document.addEventListener("pointermove", onPointerMove);
    document.addEventListener("pointerup", onPointerUp);
    document.addEventListener("pointercancel", onPointerUp);
  }

  function addPinToOverlay(minute, overlay) {
    if (overlay.querySelector(`[data-minute-id="${minute.id}"]`)) return;
    const pinEl = createPinElement(minute);
    overlay.appendChild(pinEl);
    makePinDraggable(pinEl, minute);
  }

  function attachExistingPinsForPage(pageNumber, overlay) {
    const pageMinutes = MINUTES.filter(
      (m) =>
        m.page_number != null &&
        Number(m.page_number) === Number(pageNumber) &&
        m.pos_x != null &&
        m.pos_y != null
    );
    pageMinutes.forEach((m) => addPinToOverlay(m, overlay));
  }

  // DIRECTLY open drawing editor (no choice)
  function showDrawingEditor({ overlay, x, y, pageNumber, overlayRect }) {
    if (document.querySelector(".drawing-editor")) return;

    const wrapper = document.createElement("div");
    wrapper.className = "drawing-editor";
    wrapper.style.position = "absolute";
    wrapper.style.left = `${(x * 100).toFixed(6)}%`;
    wrapper.style.top = `${(y * 100).toFixed(6)}%`;
    wrapper.style.transform = "translate(-50%, -100%)";
    wrapper.style.zIndex = "95";
    wrapper.style.minWidth = "260px";
    wrapper.style.maxWidth = "420px";

    wrapper.innerHTML = `
      <div style="background:white; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 10px 30px rgba(2,6,23,0.08); padding:8px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
          <div style="font-weight:600">Handwrite note</div>
          <div style="display:flex; gap:8px;">
            <button data-role="undo" title="Undo" style="background:#e5e7eb; border:none; padding:6px 8px; border-radius:6px; cursor:pointer;">Undo</button>
            <button data-role="clear" title="Clear" style="background:#ef4444; color:white; border:none; padding:6px 8px; border-radius:6px; cursor:pointer;">Clear</button>
          </div>
        </div>
        <div style="background:#fafafa; border:1px solid #e5e7eb; border-radius:6px; overflow:hidden;">
          <canvas id="hand-canvas" width="520" height="320" style="width:100%; height:170px; touch-action:none;"></canvas>
        </div>
        <div style="display:flex; gap:8px; margin-top:8px; justify-content:flex-end;">
          <button data-role="cancel" style="padding:6px 10px; border-radius:6px; border:none; background:#6b7280; color:white;">Cancel</button>
          <button data-role="save" style="padding:6px 10px; border-radius:6px; border:none; background:#047857; color:white;">Save</button>
        </div>
        <div data-role="error" style="color:#ef4444; font-size:12px; margin-top:6px; display:none;"></div>
      </div>
    `;

    overlay.appendChild(wrapper);

    const canvas = q("#hand-canvas", wrapper);
    const ctx = canvas.getContext("2d");
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    let drawing = false;
    const strokes = [];
    let currentPath = null;

    function pointerToCanvasCoord(ev) {
      const rect = canvas.getBoundingClientRect();
      const xCanvas = ((ev.clientX - rect.left) / rect.width) * canvas.width;
      const yCanvas = ((ev.clientY - rect.top) / rect.height) * canvas.height;
      return { x: xCanvas, y: yCanvas };
    }

    canvas.addEventListener("pointerdown", (ev) => {
      ev.preventDefault();
      drawing = true;
      currentPath = [];
      canvas.setPointerCapture(ev.pointerId);
      const pt = pointerToCanvasCoord(ev);
      currentPath.push(pt);
      drawPathSegment(currentPath);
    });

    canvas.addEventListener("pointermove", (ev) => {
      if (!drawing) return;
      const pt = pointerToCanvasCoord(ev);
      currentPath.push(pt);
      drawPathSegment(currentPath);
    });

    canvas.addEventListener("pointerup", (ev) => {
      if (!drawing) return;
      drawing = false;
      try {
        canvas.releasePointerCapture(ev.pointerId);
      } catch (e) {}
      if (currentPath && currentPath.length) strokes.push(currentPath);
      currentPath = null;
    });

    canvas.addEventListener("pointercancel", (ev) => {
      if (!drawing) return;
      drawing = false;
      if (currentPath && currentPath.length) strokes.push(currentPath);
      currentPath = null;
    });

    function drawPathSegment(path) {
      if (!path || path.length === 0) return;
      ctx.lineJoin = "round";
      ctx.lineCap = "round";
      ctx.lineWidth = 4;
      ctx.strokeStyle = "#111827";
      ctx.beginPath();
      if (path.length === 1) {
        ctx.moveTo(path[0].x, path[0].y);
        ctx.lineTo(path[0].x + 0.01, path[0].y);
      } else {
        ctx.moveTo(path[0].x, path[0].y);
        for (let i = 1; i < path.length; i++) ctx.lineTo(path[i].x, path[i].y);
      }
      ctx.stroke();
    }

    function redrawAll() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      for (const p of strokes) drawPathSegment(p);
    }

    q("[data-role='undo']", wrapper).addEventListener("click", (e) => {
      e.stopPropagation();
      strokes.pop();
      redrawAll();
    });

    q("[data-role='clear']", wrapper).addEventListener("click", (e) => {
      e.stopPropagation();
      strokes.length = 0;
      redrawAll();
    });

    q("[data-role='cancel']", wrapper).addEventListener("click", (e) => {
      e.stopPropagation();
      wrapper.remove();
    });

    q("[data-role='save']", wrapper).addEventListener("click", async (e) => {
      e.stopPropagation();
      const errorDiv = q("[data-role='error']", wrapper);
      errorDiv.style.display = "none";

      try {
        await new Promise((res) => setTimeout(res, 30));
        canvas.toBlob(async (blob) => {
          if (!blob) {
            errorDiv.textContent = "Failed to export drawing";
            errorDiv.style.display = "block";
            return;
          }

          // compute normalized size relative to overlayRect
          const overlayRectNow = overlay.getBoundingClientRect();
          // We will compute normalized width/height from the displayed canvas size (in pixels in overlay)
          // Use the canvas DOM width (clientWidth) as the drawing width in overlay coordinate
          const canvasDisplayRect = canvas.getBoundingClientRect();
          const drawnPixelW = canvasDisplayRect.width;
          const drawnPixelH = canvasDisplayRect.height;

          // Normalized w/h relative to overlay width/height
          const normW = drawnPixelW / overlayRectNow.width;
          const normH = drawnPixelH / overlayRectNow.height;

          const fd = new FormData();
          fd.append("body", "Handwritten note");
          fd.append("visibility", "public");
          fd.append("page_number", pageNumber);
          fd.append("pos_x", x);
          fd.append("pos_y", y);
          fd.append("box_style", JSON.stringify({ w: normW, h: normH }));

          fd.append("attachment", blob, `handwritten-${Date.now()}.png`);

          try {
            const res = await fetch(`/documents/${DOCUMENT_ID}/minutes`, {
              method: "POST",
              body: fd,
              credentials: "include",
              headers: { "X-CSRF-TOKEN": CSRF, Accept: "application/json" },
            });

            const data = await res.json();
            if (!res.ok) {
              const firstErr = data?.errors
                ? Object.values(data.errors)[0][0] || "Error"
                : data.message || "Error saving";
              errorDiv.textContent = firstErr;
              errorDiv.style.display = "block";
              return;
            }

            if (data.success && data.minute) {
              // Insert pin that shows the saved image with the normalized box style (exact placement/size)
              addPinToOverlay(data.minute, overlay);
              wrapper.remove();
            } else {
              errorDiv.textContent = "Unexpected server response";
              errorDiv.style.display = "block";
            }
          } catch (err) {
            console.error(err);
            errorDiv.textContent = "Network error while saving";
            errorDiv.style.display = "block";
          }
        }, "image/png");
      } catch (err) {
        console.error(err);
        errorDiv.textContent = "Failed to export image";
        errorDiv.style.display = "block";
      }
    });

    wrapper.addEventListener("click", (e) => e.stopPropagation());
  }

  (async function boot() {
    try {
      await ensureWorker();
      const loadingTask = pdfjsLib.getDocument({
        url: PREVIEW_URL,
        withCredentials: true,
      });
      const pdf = await loadingTask.promise;

      if (loadingEl) loadingEl.remove();
      container.innerHTML = "";

      for (let p = 1; p <= pdf.numPages; p++) {
        const page = await pdf.getPage(p);
        const viewport = page.getViewport({ scale: 1.3 });

        const pageWrapper = document.createElement("div");
        pageWrapper.className = "page-wrap relative bg-white mx-auto my-4";
        pageWrapper.style.maxWidth = viewport.width + "px";
        pageWrapper.style.width = "100%";
        pageWrapper.style.position = "relative";

        const canvas = document.createElement("canvas");
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        canvas.style.width = "100%";
        canvas.style.height = "auto";
        canvas.dataset.pageNumber = p;

        const overlay = document.createElement("div");
        overlay.className = "page-overlay";
        overlay.style.position = "absolute";
        overlay.style.left = "0";
        overlay.style.top = "0";
        overlay.style.width = "100%";
        overlay.style.height = "100%";
        overlay.style.pointerEvents = "auto";

        pageWrapper.appendChild(canvas);
        pageWrapper.appendChild(overlay);
        container.appendChild(pageWrapper);

        const ctx = canvas.getContext("2d");
        await page.render({ canvasContext: ctx, viewport }).promise;

        attachExistingPinsForPage(p, overlay);

        overlay.addEventListener(
          "click",
          (evt) => {
            if (!CAN_CREATE) return;

            // don't open editor if an editor already exists
            if (document.querySelector(".drawing-editor")) return;

            const rect = canvas.getBoundingClientRect();
            const x = (evt.clientX - rect.left) / rect.width;
            const y = (evt.clientY - rect.top) / rect.height;
            const xNorm = Math.max(0, Math.min(1, x));
            const yNorm = Math.max(0, Math.min(1, y));

            // pass overlay rect so we can compute normalized sizes later
            const overlayRect = overlay.getBoundingClientRect();
            showDrawingEditor({
              overlay,
              x: xNorm,
              y: yNorm,
              pageNumber: p,
              overlayRect,
            });
          },
          { passive: true }
        );
      }

      if (scrollContainer) scrollContainer.scrollTop = 0;
    } catch (err) {
      console.error("Document viewer error:", err);
      if (loadingEl) loadingEl.remove();
      container.innerHTML = `<div class="p-6 text-red-600">Failed to load document for viewing. See console for details.</div>`;
    }
  })();
})();
