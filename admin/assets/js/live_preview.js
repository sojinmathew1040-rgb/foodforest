/**
 * Food Forest Sanctuary — Live Real-Time Multi-Resolution Preview Controller
 * Luxury Split-Screen Live Synchronization & Responsive Viewport Suite
 */

(function() {
    'use strict';

    // State
    var currentDeviceCategory = 'desktop'; // desktop | tablet | mobile | custom
    var currentWidth = 1440;
    var currentHeight = 900;
    var isFluid = true;
    var isAutoFit = true;
    var isLandscape = true;

    // DOM Elements
    var previewIframe = null;
    var previewWrapper = null;
    var previewViewportBox = null;
    var resBadge = null;
    var resSelect = null;
    var customRow = null;
    var customWInput = null;
    var customHInput = null;
    var btnAutoFit = null;

    document.addEventListener('DOMContentLoaded', function() {
        initDOMElements();
        initLivePreviewSync();
        initResizeListener();
        
        // Initial setup to Fluid Responsive (100%) View
        setDeviceCategory('desktop', false);
        if (resSelect) resSelect.value = 'fluid';
        applyFluidResolution();

        // Re-render after browser completes initial layout pass
        setTimeout(function() {
            if (!isFluid) renderScaledViewport();
            else applyFluidResolution();
        }, 120);
        setTimeout(function() {
            if (!isFluid) renderScaledViewport();
            else applyFluidResolution();
        }, 400);
    });

    function initDOMElements() {
        previewIframe = document.getElementById('adm-live-preview-iframe');
        previewWrapper = document.getElementById('adm-preview-frame-wrapper');
        previewViewportBox = document.getElementById('adm-preview-viewport-box');
        resBadge = document.getElementById('adm-pv-res-badge');
        resSelect = document.getElementById('adm-pv-res-select');
        customRow = document.getElementById('adm-pv-custom-row');
        customWInput = document.getElementById('adm-pv-custom-w');
        customHInput = document.getElementById('adm-pv-custom-h');
        btnAutoFit = document.getElementById('btn-pv-autofit');
    }

    // =========================================================================
    // Multi-Device & Resolution Management
    // =========================================================================

    function setDeviceCategory(category, shouldApplyDefault) {
        currentDeviceCategory = category;
        if (shouldApplyDefault === undefined) shouldApplyDefault = true;

        // Update segmented buttons
        document.querySelectorAll('.adm-pv-seg-btn').forEach(function(btn) {
            if (btn.getAttribute('data-device') === category) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Toggle custom row vs preset select
        if (category === 'custom') {
            if (customRow) customRow.style.display = 'flex';
            if (resSelect) {
                var selectWrap = resSelect.closest('.adm-pv-res-select-wrap');
                if (selectWrap) selectWrap.style.display = 'none';
            }
            if (shouldApplyDefault) {
                applyCustomResolution();
            }
        } else {
            if (customRow) customRow.style.display = 'none';
            if (resSelect) {
                var selectWrap = resSelect.closest('.adm-pv-res-select-wrap');
                if (selectWrap) selectWrap.style.display = 'block';
                
                // Filter dropdown or select appropriate default
                if (shouldApplyDefault) {
                    if (category === 'desktop') {
                        resSelect.value = 'fluid';
                        applyFluidResolution();
                    } else if (category === 'tablet') {
                        resSelect.value = '768x1024';
                        applyResolution(768, 1024, false);
                    } else if (category === 'mobile') {
                        resSelect.value = '393x852';
                        applyResolution(393, 852, false);
                    }
                }
            }
        }
    }

    function onPresetResolutionChange(val) {
        if (!val) return;
        if (val === 'fluid') {
            applyFluidResolution();
            return;
        }

        var parts = val.split('x');
        if (parts.length === 2) {
            var w = parseInt(parts[0], 10);
            var h = parseInt(parts[1], 10);
            applyResolution(w, h, false);
        }
    }

    function applyCustomResolution() {
        if (!customWInput || !customHInput) return;
        var w = parseInt(customWInput.value, 10) || 1200;
        var h = parseInt(customHInput.value, 10) || 800;
        w = Math.max(280, Math.min(3840, w));
        h = Math.max(300, Math.min(2160, h));
        customWInput.value = w;
        customHInput.value = h;
        applyResolution(w, h, false);
    }

    function applyFluidResolution() {
        isFluid = true;
        if (!previewIframe || !previewWrapper || !previewViewportBox) return;

        previewViewportBox.classList.remove('is-mobile-device', 'is-tablet-device');
        previewViewportBox.classList.add('is-desktop-device');

        previewWrapper.style.width = '100%';
        previewWrapper.style.height = '100%';
        previewWrapper.style.minHeight = '480px';
        previewWrapper.style.transform = 'none';

        previewIframe.style.width = '100%';
        previewIframe.style.height = '100%';
        previewIframe.style.minHeight = '480px';
        previewIframe.style.transform = 'none';

        if (resBadge) {
            resBadge.textContent = '100% Fluid';
        }
    }

    function applyResolution(w, h, isRotation) {
        isFluid = false;
        currentWidth = w;
        currentHeight = h;
        isLandscape = (w >= h);

        if (!previewIframe || !previewWrapper || !previewViewportBox) return;

        // Device class for mock frames
        previewViewportBox.classList.remove('is-mobile-device', 'is-tablet-device', 'is-desktop-device');
        if (currentDeviceCategory === 'mobile' || (w <= 480 && !isLandscape)) {
            previewViewportBox.classList.add('is-mobile-device');
        } else if (currentDeviceCategory === 'tablet' || (w <= 1024 && h >= 768)) {
            previewViewportBox.classList.add('is-tablet-device');
        } else {
            previewViewportBox.classList.add('is-desktop-device');
        }

        renderScaledViewport();
    }

    function renderScaledViewport() {
        if (isFluid) return;
        if (!previewIframe || !previewWrapper || !previewViewportBox) {
            initDOMElements();
            if (!previewIframe || !previewWrapper || !previewViewportBox) return;
        }

        // Available canvas dimensions with padding
        var padX = 24;
        var padY = 24;
        var boxW = previewViewportBox.clientWidth ? (previewViewportBox.clientWidth - padX) : 0;
        var boxH = previewViewportBox.clientHeight ? (previewViewportBox.clientHeight - padY) : 0;

        if (boxW < 100) {
            boxW = previewViewportBox.offsetWidth ? (previewViewportBox.offsetWidth - padX) : 560;
        }
        if (boxH < 100) {
            boxH = previewViewportBox.offsetHeight ? (previewViewportBox.offsetHeight - padY) : 480;
        }
        if (boxW < 100) boxW = 560;
        if (boxH < 100) boxH = 480;

        var scale = 1;
        if (isAutoFit) {
            var scaleX = boxW / currentWidth;
            var scaleY = boxH / currentHeight;
            scale = Math.min(scaleX, scaleY, 1);
            scale = Math.max(0.18, scale); // Minimum positive floor
        }

        var renderedW = Math.round(currentWidth * scale);
        var renderedH = Math.round(currentHeight * scale);

        // Position wrapper container
        previewWrapper.style.width = renderedW + 'px';
        previewWrapper.style.height = renderedH + 'px';
        previewWrapper.style.maxWidth = 'none';
        previewWrapper.style.maxHeight = 'none';
        previewWrapper.style.display = 'flex';
        previewWrapper.style.alignItems = 'center';
        previewWrapper.style.justifyContent = 'center';

        // Size and scale the iframe directly
        previewIframe.style.width = currentWidth + 'px';
        previewIframe.style.height = currentHeight + 'px';
        previewIframe.style.transform = 'scale(' + scale + ')';
        previewIframe.style.transformOrigin = 'center center';
        previewIframe.style.display = 'block';

        // Update active resolution badge
        if (resBadge) {
            var scalePct = Math.round(scale * 100);
            if (scale < 0.99) {
                resBadge.textContent = currentWidth + ' × ' + currentHeight + ' (' + scalePct + '%)';
            } else {
                resBadge.textContent = currentWidth + ' × ' + currentHeight;
            }
        }
    }

    function togglePreviewOrientation() {
        if (isFluid) return;
        var newW = currentHeight;
        var newH = currentWidth;
        applyResolution(newW, newH, true);
    }

    function togglePreviewAutoFit() {
        isAutoFit = !isAutoFit;
        if (btnAutoFit) {
            if (isAutoFit) {
                btnAutoFit.classList.add('active');
                btnAutoFit.title = 'Auto Fit Scale Active (Click for 100% Scroll View)';
            } else {
                btnAutoFit.classList.remove('active');
                btnAutoFit.title = '100% Scroll View Active (Click for Auto Fit Scale)';
            }
        }
        renderScaledViewport();
    }

    function reloadPreviewFrame() {
        if (previewIframe) {
            previewIframe.src = previewIframe.src;
        }
    }

    function initResizeListener() {
        window.addEventListener('resize', debounce(function() {
            if (!isFluid) {
                renderScaledViewport();
            }
        }, 80));

        // Also listen for sidebar toggle transitions
        var sidebarToggle = document.getElementById('adm-sidebar-toggle-btn');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                setTimeout(function() {
                    if (!isFluid) renderScaledViewport();
                }, 300);
            });
        }
    }

    // =========================================================================
    // 2-Way Real-Time Form Synchronization
    // =========================================================================

    function initLivePreviewSync() {
        if (!previewIframe) return;

        // Collect all input elements in active settings forms
        var forms = document.querySelectorAll('.adm-settings-tab-pane form');
        forms.forEach(function(form) {
            form.addEventListener('input', function(e) {
                syncFormWithPreview(form, previewIframe);
            });

            form.addEventListener('change', function(e) {
                syncFormWithPreview(form, previewIframe);

                // Handle file image inputs for instant visual preview
                if (e.target && e.target.type === 'file' && e.target.files && e.target.files[0]) {
                    var file = e.target.files[0];
                    if (file.type.match('image.*')) {
                        var reader = new FileReader();
                        reader.onload = function(evt) {
                            var dataUrl = evt.target.result;
                            var fieldName = e.target.name;

                            if (fieldName === 'hero_bg_image_file') {
                                postToPreview(previewIframe, {
                                    type: 'update_image',
                                    selector: '#pv-hero-bg',
                                    src: dataUrl
                                });
                            } else if (fieldName === 'welcome_image_file') {
                                postToPreview(previewIframe, {
                                    type: 'update_image',
                                    selector: '#pv-wel-img',
                                    src: dataUrl
                                });
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
        });

        // Initial sync after iframe loads
        previewIframe.addEventListener('load', function() {
            renderScaledViewport();
            var activePane = document.querySelector('.adm-settings-tab-pane.is-active');
            if (activePane) {
                var activeForm = activePane.querySelector('form');
                if (activeForm) {
                    syncFormWithPreview(activeForm, previewIframe);
                }
            }
        });
    }

    function syncFormWithPreview(form, iframe) {
        if (!iframe || !iframe.contentWindow) return;

        var formData = new FormData(form);
        var fields = {};
        formData.forEach(function(value, key) {
            fields[key] = value;
        });

        // Add values of any inputs not captured by FormData
        var inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(inp) {
            if (inp.name) {
                fields[inp.name] = inp.value;
            }
        });

        postToPreview(iframe, {
            type: 'sync_fields',
            fields: fields
        });
    }

    function postToPreview(iframe, message) {
        try {
            if (iframe && iframe.contentWindow) {
                iframe.contentWindow.postMessage(message, '*');
            }
        } catch(err) {
            console.warn('Preview sync message error:', err);
        }
    }

    function debounce(fn, delay) {
        var timer = null;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, delay);
        };
    }

    // Expose functions globally for inline HTML button triggers
    window.setDeviceCategory = setDeviceCategory;
    window.onPresetResolutionChange = onPresetResolutionChange;
    window.applyCustomResolution = applyCustomResolution;
    window.togglePreviewOrientation = togglePreviewOrientation;
    window.togglePreviewAutoFit = togglePreviewAutoFit;
    window.reloadPreviewFrame = reloadPreviewFrame;
    window.renderScaledViewport = renderScaledViewport;

})();
