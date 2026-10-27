@extends('layouts.app')

@section('layout-role', 'patient')

@section('title', 'Re-upload Signature')

@section('content')
    <main id="mainContent" class="app-page-shell page-enter patient-signature-review-page">
        <div class="mx-auto w-full max-w-[1480px] px-4 md:px-5 xl:px-6">
            <div
                class="relative overflow-hidden rounded-[32px] border border-[#f0dfd7] bg-[radial-gradient(circle_at_top_left,_rgba(139,0,0,0.08),_transparent_35%),linear-gradient(180deg,_#fffdfc_0%,_#fff8f5_100%)] shadow-[0_24px_60px_rgba(90,40,24,0.08)]">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#8B0000] via-[#b32121] to-[#d96d5c]"></div>

                <div class="grid gap-6 px-5 py-5 md:px-7 md:py-6 xl:grid-cols-[0.9fr_1.1fr]">
                    <section class="space-y-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.34em] text-[#8B0000]/75">Patient Signature
                                </p>
                                <h1
                                    class="mt-3 whitespace-nowrap text-[2rem] font-extrabold leading-[1.03] text-[#2b1f1f] md:text-[2.3rem]">
                                    Re-submit your clinic signature
                                </h1>
                                <p class="mt-3 max-w-[560px] text-[13.5px] leading-6 text-[#5c5550]">
                                    Your last signature was flagged during manual review. Please upload a clearer signature
                                    image
                                    or draw a fresh one below so the clinic can validate your record properly.
                                </p>
                            </div>

                            <a href="{{ route('homepage') }}"
                                class="inline-flex items-center gap-2 self-start whitespace-nowrap rounded-2xl border border-[#e8e2dd] bg-white px-4 py-2 text-[13px] font-bold text-[#4b5563] shadow-sm transition hover:border-[#8B0000] hover:text-[#8B0000] lg:mt-2">
                                <i class="fa-solid fa-arrow-left text-xs"></i>
                                Back to Home
                            </a>
                        </div>

                        <div class="rounded-[28px] border border-[#f1d7d0] bg-white/90 p-4 shadow-sm md:p-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div
                                    class="inline-flex items-center gap-2 rounded-full bg-[#fff1ef] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[#a31616]">
                                    <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                    Re-upload Required
                                </div>

                                @if ($medicalHistory->patient_signature)
                                    <a href="{{ asset('storage/' . $medicalHistory->patient_signature) }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 rounded-full border border-[#f0d7d0] bg-[#fff7f5] px-3 py-1.5 text-[11px] font-bold text-[#8B0000] transition hover:border-[#8B0000] hover:bg-white">
                                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                                        Open Previous Image
                                    </a>
                                @endif
                            </div>

                            <div class="mt-4 grid gap-4 lg:grid-cols-[0.88fr_1.12fr]">
                                <div class="space-y-4">
                                    <div class="rounded-2xl border border-[#f4d5cf] bg-[#fff7f5] p-4">
                                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">Review
                                            Note</p>
                                        <p class="mt-3 text-[13px] leading-6 text-[#7b2d26]">
                                            {{ $medicalHistory->signature_review_notes ?: 'The uploaded image did not appear to be a valid patient signature.' }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl border border-[#efe5de] bg-[#fffdfa] p-4">
                                        <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">What To
                                            Submit</p>
                                        <div class="mt-3 space-y-2 text-[13px] leading-5 text-[#5c5550]">
                                            <p><i class="fa-solid fa-check mr-2 text-[#8B0000]"></i>Your actual handwritten
                                                signature only</p>
                                            <p><i class="fa-solid fa-check mr-2 text-[#8B0000]"></i>Clear background and
                                                readable strokes</p>
                                            <p><i class="fa-solid fa-xmark mr-2 text-[#b45309]"></i>No selfies, posters,
                                                screenshots, or documents</p>
                                        </div>
                                    </div>
                                </div>

                                @if ($medicalHistory->patient_signature)
                                    <div class="rounded-2xl border border-[#efe5de] bg-[#fffdfa] p-4">
                                        <div>
                                            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">
                                                Previous Uploaded Image</p>
                                            <p class="mt-1 text-sm text-[#7a746d]">This is the image that was marked
                                                invalid.</p>
                                        </div>

                                        <div
                                            class="mt-4 overflow-hidden rounded-[24px] border border-[#eadfd8] bg-[#f8f5f2]">
                                            <img src="{{ asset('storage/' . $medicalHistory->patient_signature) }}"
                                                alt="Previous uploaded signature"
                                                class="h-full max-h-[360px] w-full object-contain">
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-[30px] border border-[#eadfd8] bg-white p-5 shadow-[0_20px_50px_rgba(83,34,18,0.08)] md:p-6">
                        <div class="mb-4">
                            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-[#8B0000]">Submit A New
                                Signature</p>
                            <h2 class="mt-2 text-2xl font-extrabold text-[#2b1f1f]">Upload or draw your signature</h2>
                            <p class="mt-2 text-[13px] leading-6 text-[#6b6470]">
                                You can either upload a signature image or draw directly here using your mouse, touch, or
                                stylus.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('patient.signature-review.update') }}"
                            enctype="multipart/form-data" class="space-y-4" id="signatureReviewForm">
                            @csrf

                            <div class="space-y-4">
                                <div
                                    class="inline-flex w-fit max-w-full rounded-[22px] border border-[#e8e2dd] bg-[#faf7f4] p-1 shadow-sm">
                                    <button type="button" id="uploadTabBtn"
                                        class="signature-mode-btn is-active inline-flex items-center gap-1 rounded-[14px] px-3 py-1 text-[10px] font-semibold text-[#8B0000] transition"
                                        aria-pressed="true">
                                        <i class="fa-regular fa-image text-[10px]"></i>
                                        Upload a Signature
                                    </button>
                                    <button type="button" id="drawTabBtn"
                                        class="signature-mode-btn inline-flex items-center gap-1 rounded-[14px] px-3 py-1 text-[10px] font-semibold text-[#6b6470] transition"
                                        aria-pressed="false">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                        Draw a Signature
                                    </button>
                                </div>

                                <div id="uploadPanel"
                                    class="signature-mode-panel rounded-[28px] border border-dashed border-[#e3d7d0] bg-[#fffdfc] p-5">
                                    <div class="flex min-h-[270px] flex-col items-center justify-center text-center">
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#fff2ef] text-[#8B0000] shadow-sm">
                                            <i class="fa-solid fa-upload text-[22px]"></i>
                                        </div>

                                        <p class="mt-4 text-[13px] font-semibold leading-6 text-[#4f4945]">
                                            Click to choose a file, or drag it here
                                        </p>
                                        <p class="mt-1 text-xs text-[#9e9690]">JPG or PNG, up to 25 MB</p>

                                        <label
                                            class="mt-5 inline-flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-2xl bg-[#8B0000] px-4 py-2.5 text-[12px] font-bold text-white shadow-[0_12px_28px_rgba(139,0,0,0.18)] transition hover:bg-[#6b0000]">
                                            <i class="fa-solid fa-upload text-xs"></i>
                                            Browse Signature
                                            <input type="file" name="patient_signature" id="patient_signature"
                                                class="hidden" accept=".jpg,.jpeg,.png,image/png,image/jpeg">
                                        </label>
                                    </div>
                                </div>

                                <div id="drawPanel"
                                    class="signature-mode-panel hidden rounded-[28px] border border-[#efe4dc] bg-[#fffdfb] p-5">
                                    <p class="text-center text-[13px] font-extrabold text-[#8B0000]">Or draw your signature
                                        here</p>

                                    <div
                                        class="mt-4 overflow-hidden rounded-3xl border-2 border-dashed border-[#ddd4cf] bg-white p-3">
                                        <canvas id="signatureCanvas"
                                            class="block h-[190px] w-full touch-none rounded-2xl bg-white"></canvas>
                                    </div>

                                    <p class="mt-4 text-center text-xs text-[#9e9690]">Use mouse, touch, or stylus.</p>

                                    <div class="mt-5 flex items-center gap-2.5">
                                        <button type="button" id="signatureUndoBtn"
                                            class="inline-flex min-w-[82px] items-center justify-center whitespace-nowrap rounded-[16px] border border-[#e8e2dd] bg-white px-3 py-2 text-[11px] font-bold text-[#6b625c] shadow-sm transition hover:border-[#8B0000] hover:text-[#8B0000]">
                                            Undo
                                        </button>

                                        <button type="button" id="signatureClearBtn"
                                            class="inline-flex min-w-[126px] items-center justify-center whitespace-nowrap rounded-[16px] border border-[#e8e2dd] bg-white px-3 py-2 text-[11px] font-bold text-[#6b625c] shadow-sm transition hover:border-[#8B0000] hover:text-[#8B0000]">
                                            Clear Signature
                                        </button>

                                        <button type="button" id="signatureUseDrawnBtn"
                                            class="inline-flex min-w-[206px] items-center justify-center gap-1.5 whitespace-nowrap rounded-[16px] bg-[#8B0000] px-3.5 py-2 text-[11px] font-bold text-white shadow-[0_14px_30px_rgba(139,0,0,0.18)] transition hover:bg-[#6b0000]">
                                            <i class="fa-solid fa-pen-nib text-[9px]"></i>
                                            Use Drawn Signature
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="signature_source" id="signature_source" value="">
                            </div>

                            <div id="signature_result_box"
                                class="hidden rounded-2xl border border-[#e8e2dd] bg-[#fffdfa] p-4">
                                <p id="signature_filename" class="truncate text-xs font-bold text-[#5c5550]"></p>
                                <div id="signature_error"
                                    class="mt-2 hidden rounded-2xl px-3 py-3 text-xs font-semibold leading-5"></div>
                            </div>

                            @error('patient_signature')
                                <div
                                    class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                    <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ $message }}
                                </div>
                            @enderror

                            <div class="rounded-3xl border border-[#f3e5b8] bg-[#fff7df] p-4 text-[13px] text-[#8a5310]">
                                <p class="font-bold">Tip</p>
                                <p class="mt-1 leading-6">
                                    If you change your drawing after clicking `Use Drawn Signature`, click it again so the
                                    new version becomes the one that gets submitted.
                                </p>
                            </div>

                            <button type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-[20px] bg-[#8B0000] px-5 py-3.5 text-[13px] font-bold text-white shadow-[0_18px_34px_rgba(139,0,0,0.18)] transition hover:bg-[#6b0000]">
                                <i class="fa-solid fa-upload text-xs"></i>
                                Submit New Signature
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        const sigInput = document.getElementById("patient_signature");
        const signatureSourceInput = document.getElementById("signature_source");
        const sigName = document.getElementById("signature_filename");
        const sigError = document.getElementById("signature_error");
        const sigResultBox = document.getElementById("signature_result_box");

        const SIGNATURE_ACCEPTED_MESSAGE = "Signature verified and accepted";
        const SIGNATURE_DECLINED_MESSAGE = "Signature could not be processed. Please try again.";

        let signatureAiValid = false;
        let signatureAiChecking = false;

        function escapeHtml(value) {
            return String(value || "")
                .replaceAll("&", "&amp;")
                .replaceAll("<", "&lt;")
                .replaceAll(">", "&gt;")
                .replaceAll('"', "&quot;")
                .replaceAll("'", "&#039;");
        }

        function clearSignatureDisplay() {
            sigResultBox?.classList.add("hidden");

            if (sigName) {
                sigName.textContent = "";
                sigName.className = "truncate text-xs font-bold text-[#5c5550]";
            }

            if (sigError) {
                sigError.innerHTML = "";
                sigError.classList.add("hidden");
            }
        }

        function showSignatureStatus(fileName = "", message = "", type = "neutral") {
            sigResultBox?.classList.remove("hidden");

            if (sigName) {
                sigName.textContent = fileName;
                sigName.className = "truncate text-xs font-bold";

                if (type === "success") {
                    sigName.classList.add("text-emerald-700");
                } else if (type === "error") {
                    sigName.classList.add("text-red-600");
                } else {
                    sigName.classList.add("text-[#5c5550]");
                }
            }

            if (!sigError) {
                return;
            }

            let icon = `<i class="fa-solid fa-spinner fa-spin mr-1"></i>`;
            let className =
                "mt-2 rounded-2xl border border-[#e8e2dd] bg-white px-3 py-3 text-xs text-[#5c5550] leading-5 font-semibold";

            if (type === "success") {
                icon = `<i class="fa-solid fa-circle-check mr-1"></i>`;
                className =
                    "mt-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-3 text-xs text-emerald-700 leading-5 font-semibold";
            } else if (type === "error") {
                icon = `<i class="fa-solid fa-circle-exclamation mr-1"></i>`;
                className =
                    "mt-2 rounded-2xl border border-red-200 bg-red-50 px-3 py-3 text-xs text-red-700 leading-5 font-semibold";
            }

            sigError.className = className;
            sigError.innerHTML = `${icon}${escapeHtml(message)}`;
            sigError.classList.remove("hidden");
        }

        function showSignatureError(fileName = "", result = {}) {
            signatureAiValid = false;
            signatureAiChecking = false;

            const aiReason = result.reason || "The uploaded image did not pass signature validation.";
            const detectedType = result.detected_type || "unknown";

            sigResultBox?.classList.remove("hidden");

            if (sigName) {
                sigName.textContent = fileName;
                sigName.className = "truncate text-xs font-bold text-red-600";
            }

            if (sigError) {
                sigError.className =
                    "mt-2 rounded-2xl border border-red-200 bg-red-50 px-3 py-3 text-xs text-red-700 leading-5 font-semibold";
                sigError.innerHTML = `
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p>${escapeHtml(SIGNATURE_DECLINED_MESSAGE)}</p>
                        <p class="mt-1 font-medium">Reason: ${escapeHtml(aiReason)}</p>
                       <p class="mt-1 text-[0.7rem] opacity-80">
                            Detected: ${escapeHtml(detectedType)}
                        </p>
                        </p>
                    </div>
                </div>
            `;
                sigError.classList.remove("hidden");
            }

            if (sigInput) {
                sigInput.value = "";
            }
        }

        function isDrawnSignatureFile(file) {
            return file?.name?.startsWith("drawn-signature-");
        }

        async function validateSignatureWithAi(file) {
            const formData = new FormData();
            formData.append("patient_signature", file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");

            const response = await fetch("{{ route('book.appointment.validate-signature') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json",
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.valid === false || data.accepted === false) {
                const error = new Error(data.message || SIGNATURE_DECLINED_MESSAGE);
                error.data = data;
                throw error;
            }

            return data;
        }

        sigInput?.addEventListener("change", () => {
            const file = sigInput.files?.[0];
            const isDrawnSignature = isDrawnSignatureFile(file);

            signatureAiValid = false;

            if (!file) {
                if (signatureSourceInput) signatureSourceInput.value = "";
                clearSignatureDisplay();
                return;
            }

            if (signatureSourceInput) {
                signatureSourceInput.value = isDrawnSignature ? "drawn" : "";
            }

            const allowedTypes = ["image/jpeg", "image/png"];
            const maxSize = 25 * 1024 * 1024;

            if (!allowedTypes.includes(file.type)) {
                showSignatureError(file?.name || "", {
                    reason: "Signature must be a JPG or PNG file.",
                    detected_type: "invalid_file_type",
                    confidence: 0,
                });
                return;
            }

            if (file.size > maxSize) {
                showSignatureError(file.name, {
                    reason: "Signature file must not exceed 25 MB.",
                    detected_type: "file_too_large",
                    confidence: 0,
                });
                return;
            }

            if (isDrawnSignature) {
                drawnSignatureWasUsed = true;
                signatureAiValid = true;
                signatureAiChecking = false;
                showSignatureStatus(file.name, "Drawn signature accepted.", "success");
                return;
            }

            const img = new Image();
            const objectUrl = URL.createObjectURL(file);

            img.onload = async function() {
                const {
                    width,
                    height
                } = img;

                if (width < 120 || height < 60) {
                    URL.revokeObjectURL(objectUrl);
                    showSignatureError(file.name, {
                        reason: "Signature image is too small. Please upload a clearer signature.",
                        detected_type: "image_too_small",
                        confidence: 0,
                    });
                    return;
                }

                if (width > 5000 || height > 5000) {
                    URL.revokeObjectURL(objectUrl);
                    showSignatureError(file.name, {
                        reason: "Signature image is too large. Please upload a smaller image.",
                        detected_type: "image_too_large",
                        confidence: 0,
                    });
                    return;
                }

                URL.revokeObjectURL(objectUrl);

                try {
                    signatureAiChecking = true;
                    showSignatureStatus(file.name, "Checking signature image...", "neutral");

                    await validateSignatureWithAi(file);

                    signatureAiValid = true;
                    signatureAiChecking = false;
                    showSignatureStatus(file.name, SIGNATURE_ACCEPTED_MESSAGE, "success");
                } catch (error) {
                    signatureAiValid = false;
                    signatureAiChecking = false;

                    showSignatureError(file.name, error.data || {
                        message: SIGNATURE_DECLINED_MESSAGE,
                        reason: error.message || "Unable to validate the uploaded image.",
                        detected_type: "unknown",
                        confidence: 0,
                    });
                }
            };

            img.onerror = function() {
                URL.revokeObjectURL(objectUrl);
                showSignatureError(file?.name || "", {
                    reason: "Invalid signature image file.",
                    detected_type: "invalid_image",
                    confidence: 0,
                });
            };

            img.src = objectUrl;
        });

        const signatureCanvas = document.getElementById("signatureCanvas");
        const signatureCtx = signatureCanvas?.getContext("2d");
        const signatureUndoBtn = document.getElementById("signatureUndoBtn");
        const signatureClearBtn = document.getElementById("signatureClearBtn");
        const signatureUseDrawnBtn = document.getElementById("signatureUseDrawnBtn");
        const uploadTabBtn = document.getElementById("uploadTabBtn");
        const drawTabBtn = document.getElementById("drawTabBtn");
        const uploadPanel = document.getElementById("uploadPanel");
        const drawPanel = document.getElementById("drawPanel");

        let drawnSignatureStrokes = [];
        let drawnSignatureCurrentStroke = [];
        let drawnSignatureIsDrawing = false;
        let drawnSignatureWasUsed = false;

        function setSignatureMode(mode) {
            const isUpload = mode === "upload";

            uploadPanel?.classList.toggle("hidden", !isUpload);
            drawPanel?.classList.toggle("hidden", isUpload);

            uploadTabBtn?.setAttribute("aria-pressed", isUpload ? "true" : "false");
            drawTabBtn?.setAttribute("aria-pressed", isUpload ? "false" : "true");

            uploadTabBtn?.classList.toggle("is-active", isUpload);
            drawTabBtn?.classList.toggle("is-active", !isUpload);

            if (uploadTabBtn) {
                uploadTabBtn.classList.toggle("bg-white", isUpload);
                uploadTabBtn.classList.toggle("text-[#8B0000]", isUpload);
                uploadTabBtn.classList.toggle("shadow-sm", isUpload);
                uploadTabBtn.classList.toggle("text-[#6b6470]", !isUpload);
            }

            if (drawTabBtn) {
                drawTabBtn.classList.toggle("bg-white", !isUpload);
                drawTabBtn.classList.toggle("text-[#8B0000]", !isUpload);
                drawTabBtn.classList.toggle("shadow-sm", !isUpload);
                drawTabBtn.classList.toggle("text-[#6b6470]", isUpload);
            }

            if (!isUpload) {
                setTimeout(resizeSignatureCanvas, 60);
            }
        }

        function resizeSignatureCanvas() {
            if (!signatureCanvas || !signatureCtx) return;

            const rect = signatureCanvas.getBoundingClientRect();
            if (!rect.width) return;

            const dpr = Math.max(window.devicePixelRatio || 1, 1);
            const cssWidth = rect.width;
            const cssHeight = signatureCanvas.offsetHeight || 220;

            signatureCanvas.width = Math.round(cssWidth * dpr);
            signatureCanvas.height = Math.round(cssHeight * dpr);

            signatureCtx.setTransform(dpr, 0, 0, dpr, 0, 0);

            redrawSignatureCanvas();
        }

        function paintSignatureCanvasBackground() {
            if (!signatureCanvas || !signatureCtx) return;

            signatureCtx.save();
            signatureCtx.setTransform(1, 0, 0, 1, 0, 0);
            signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            signatureCtx.fillStyle = "#ffffff";
            signatureCtx.fillRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            signatureCtx.restore();
        }

        function drawSignatureStroke(stroke) {
            if (!signatureCtx || !stroke || !stroke.length) return;

            signatureCtx.lineWidth = 3.2;
            signatureCtx.lineCap = "round";
            signatureCtx.lineJoin = "round";
            signatureCtx.strokeStyle = "#111827";

            if (stroke.length === 1) {
                signatureCtx.beginPath();
                signatureCtx.arc(stroke[0].x, stroke[0].y, 1.8, 0, Math.PI * 2);
                signatureCtx.fillStyle = "#111827";
                signatureCtx.fill();
                return;
            }

            signatureCtx.beginPath();
            signatureCtx.moveTo(stroke[0].x, stroke[0].y);

            for (let i = 1; i < stroke.length - 1; i++) {
                const midX = (stroke[i].x + stroke[i + 1].x) / 2;
                const midY = (stroke[i].y + stroke[i + 1].y) / 2;
                signatureCtx.quadraticCurveTo(stroke[i].x, stroke[i].y, midX, midY);
            }

            const last = stroke[stroke.length - 1];
            signatureCtx.lineTo(last.x, last.y);
            signatureCtx.stroke();
        }

        function redrawSignatureCanvas() {
            if (!signatureCanvas || !signatureCtx) return;

            paintSignatureCanvasBackground();
            drawnSignatureStrokes.forEach(drawSignatureStroke);

            if (drawnSignatureCurrentStroke.length) {
                drawSignatureStroke(drawnSignatureCurrentStroke);
            }
        }

        function getSignaturePoint(event) {
            const rect = signatureCanvas.getBoundingClientRect();

            return {
                x: event.clientX - rect.left,
                y: event.clientY - rect.top,
            };
        }

        function isDrawnSignatureBlank() {
            return drawnSignatureStrokes.length === 0 && drawnSignatureCurrentStroke.length === 0;
        }

        function invalidateDrawnSignatureAfterEdit() {
            if (!drawnSignatureWasUsed) return;

            drawnSignatureWasUsed = false;
            signatureAiValid = false;

            if (sigInput) {
                sigInput.value = "";
            }

            if (signatureSourceInput) {
                signatureSourceInput.value = "";
            }

            showSignatureStatus("", "Draw changed. Click Use Drawn Signature again to submit the latest version.",
                "neutral");
        }

        function startDrawnSignature(event) {
            if (!signatureCanvas) return;
            if (event.pointerType === "mouse" && event.button !== 0) return;

            resizeSignatureCanvas();
            event.preventDefault();

            drawnSignatureIsDrawing = true;
            drawnSignatureCurrentStroke = [getSignaturePoint(event)];
            signatureCanvas.setPointerCapture?.(event.pointerId);

            redrawSignatureCanvas();
        }

        function moveDrawnSignature(event) {
            if (!drawnSignatureIsDrawing) return;

            event.preventDefault();
            drawnSignatureCurrentStroke.push(getSignaturePoint(event));
            redrawSignatureCanvas();
        }

        function endDrawnSignature(event) {
            if (!drawnSignatureIsDrawing) return;

            event.preventDefault();

            if (drawnSignatureCurrentStroke.length) {
                drawnSignatureStrokes.push(drawnSignatureCurrentStroke);
            }

            drawnSignatureCurrentStroke = [];
            drawnSignatureIsDrawing = false;
            invalidateDrawnSignatureAfterEdit();
            redrawSignatureCanvas();
        }

        function clearDrawnSignature() {
            drawnSignatureStrokes = [];
            drawnSignatureCurrentStroke = [];
            drawnSignatureIsDrawing = false;
            drawnSignatureWasUsed = false;
            signatureAiValid = false;

            redrawSignatureCanvas();

            if (sigInput) {
                sigInput.value = "";
            }

            if (signatureSourceInput) {
                signatureSourceInput.value = "";
            }

            clearSignatureDisplay();
        }

        function undoDrawnSignature() {
            if (!drawnSignatureStrokes.length) return;

            drawnSignatureStrokes.pop();
            redrawSignatureCanvas();
            invalidateDrawnSignatureAfterEdit();

            if (isDrawnSignatureBlank()) {
                drawnSignatureWasUsed = false;
                signatureAiValid = false;

                if (sigInput) {
                    sigInput.value = "";
                }

                if (signatureSourceInput) {
                    signatureSourceInput.value = "";
                }

                clearSignatureDisplay();
            }
        }

        function attachDrawnSignatureToFileInput(file) {
            if (!sigInput) return false;

            if (typeof DataTransfer === "undefined") {
                showSignatureError("", {
                    reason: "Your browser cannot attach the drawn signature. Please upload an image instead.",
                    detected_type: "browser_unsupported",
                    confidence: 0,
                });
                return false;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            sigInput.files = dataTransfer.files;

            return true;
        }

        function useDrawnSignature() {
            if (!signatureCanvas) return;

            if (isDrawnSignatureBlank()) {
                showSignatureError("", {
                    reason: "Please draw your signature first.",
                    detected_type: "blank_signature",
                    confidence: 0,
                });
                return;
            }

            redrawSignatureCanvas();

            signatureCanvas.toBlob((blob) => {
                if (!blob) {
                    showSignatureError("", {
                        reason: "Unable to process drawn signature. Please try again.",
                        detected_type: "canvas_error",
                        confidence: 0,
                    });
                    return;
                }

                const file = new File([blob], `drawn-signature-${Date.now()}.png`, {
                    type: "image/png",
                    lastModified: Date.now(),
                });

                const attached = attachDrawnSignatureToFileInput(file);
                if (!attached) return;

                drawnSignatureWasUsed = true;

                if (signatureSourceInput) {
                    signatureSourceInput.value = "drawn";
                }

                sigInput.dispatchEvent(new Event("change", {
                    bubbles: true,
                }));
            }, "image/png", 0.95);
        }

        signatureCanvas?.addEventListener("pointerdown", startDrawnSignature);
        signatureCanvas?.addEventListener("pointermove", moveDrawnSignature);
        signatureCanvas?.addEventListener("pointerup", endDrawnSignature);
        signatureCanvas?.addEventListener("pointercancel", endDrawnSignature);
        signatureCanvas?.addEventListener("pointerleave", endDrawnSignature);

        signatureUndoBtn?.addEventListener("click", undoDrawnSignature);
        signatureClearBtn?.addEventListener("click", clearDrawnSignature);
        signatureUseDrawnBtn?.addEventListener("click", useDrawnSignature);
        uploadTabBtn?.addEventListener("click", () => setSignatureMode("upload"));
        drawTabBtn?.addEventListener("click", () => setSignatureMode("draw"));

        window.addEventListener("resize", resizeSignatureCanvas);

        document.getElementById("signatureReviewForm")?.addEventListener("submit", (event) => {
            if (signatureAiChecking) {
                event.preventDefault();
                showSignatureStatus(sigInput?.files?.[0]?.name || "",
                    "Please wait while the signature is being checked.", "neutral");
                return;
            }

            if (!sigInput?.files?.length || !signatureAiValid) {
                event.preventDefault();
                showSignatureError(sigInput?.files?.[0]?.name || "", {
                    reason: "Please upload or apply a valid signature before submitting.",
                    detected_type: "missing_valid_signature",
                    confidence: 0,
                });
            }
        });

        setSignatureMode("upload");
        setTimeout(resizeSignatureCanvas, 250);
    </script>
@endsection
