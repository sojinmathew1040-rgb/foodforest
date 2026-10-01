function previewUploadImage(input, previewImgId, infoBadgeId) {
        if (input.files && input.files[0]) {
            var file = input.files[0];
            if (!file.type.match('image.*')) {
                alert('Please select an image file (JPG, PNG, WEBP, GIF, SVG).');
                input.value = '';
                return;
            }
            var previewImg = document.getElementById(previewImgId);
            if (previewImg) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
            if (infoBadgeId) {
                var badge = document.getElementById(infoBadgeId);
                if (badge) {
                    var sizeKb = Math.round(file.size / 1024);
                    var sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                    badge.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i> ' + file.name + ' <span style="opacity:0.7;margin-left:4px;">(' + sizeStr + ')</span>';
                    badge.style.display = 'inline-flex';
                }
            }
        }
    }
    window.previewUploadImage = previewUploadImage;

    function admin_img_src(path, fallback) {
        if (!fallback) fallback = '../assets/images/treehouse_exterior.png';
        if (!path || !path.trim()) return fallback;
        path = path.trim();
        if (/^https?:\/\//i.test(path) || /^data:/i.test(path)) return path;
        if (path.indexOf('../') === 0) return path;
        return '../' + path.replace(/^\/+/, '');
    }
    window.admin_img_src = admin_img_src;

    function toggleAddNewDrawer(drawerId) {
        var el = document.getElementById(drawerId);
        if (!el) return;
        var isHidden = (el.style.display === 'none' || getComputedStyle(el).display === 'none');
        if (isHidden) {
            el.style.display = 'block';
            setTimeout(function() {
                try {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch(e){}
                var firstInput = el.querySelector('input[type="text"], textarea');
                if (firstInput) firstInput.focus();
            }, 50);
        } else {
            el.style.display = 'none';
        }
    }
    function toggle360Mode(showId, hideId) {
        var showEl = document.getElementById(showId);
        var hideEl = document.getElementById(hideId);
        if (showEl) showEl.style.display = 'block';
        if (hideEl) hideEl.style.display = 'none';
    }
    window.toggle360Mode = toggle360Mode;

    function updateStitchBadge(input, badgeId) {
        var badge = document.getElementById(badgeId);
        if (!badge) return;
        if (input.files && input.files[0]) {
            var f = input.files[0];
            badge.innerHTML = '<i class="fa-solid fa-check"></i> ' + f.name.substring(0, 14) + '...';
            badge.style.display = 'block';
        }
    }
    window.updateStitchBadge = updateStitchBadge;

    function setPinFromClick(event, containerId, inputXId, inputYId, markerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var rect = container.getBoundingClientRect();
        var x = event.clientX - rect.left;
        var y = event.clientY - rect.top;
        var xPercent = Math.max(2, Math.min(98, (x / rect.width) * 100));
        var yPercent = Math.max(2, Math.min(98, (y / rect.height) * 100));
        
        xPercent = Math.round(xPercent * 10) / 10;
        yPercent = Math.round(yPercent * 10) / 10;
        
        var inputX = document.getElementById(inputXId);
        var inputY = document.getElementById(inputYId);
        var marker = document.getElementById(markerId);
        
        if (inputX) inputX.value = xPercent;
        if (inputY) inputY.value = yPercent;
        if (marker) {
            marker.style.left = xPercent + '%';
            marker.style.top = yPercent + '%';
        }
    }
    window.setPinFromClick = setPinFromClick;

    function updatePinFromInput(inputXId, inputYId, markerId) {
        var inputX = document.getElementById(inputXId);
        var inputY = document.getElementById(inputYId);
        var marker = document.getElementById(markerId);
        if (!marker) return;
        var x = inputX ? parseFloat(inputX.value) || 50 : 50;
        var y = inputY ? parseFloat(inputY.value) || 50 : 50;
        marker.style.left = Math.max(2, Math.min(98, x)) + '%';
        marker.style.top = Math.max(2, Math.min(98, y)) + '%';
    }
    window.updatePinFromInput = updatePinFromInput;

    /* =========================================================================
       Sanctuary Master Map Studio: Interactive Pathway & Curve Drawing Engine
       ========================================================================= */
    function initAdminMasterMapStudio() {
        var canvas = document.getElementById('admin-master-map-canvas');
        if (!canvas) return;

        var routeDataInput = document.getElementById('sanctuary_map_route_data');
        var mapConfig = null;
        try {
            if (routeDataInput && routeDataInput.value) {
                mapConfig = JSON.parse(routeDataInput.value);
            }
        } catch(e) {
            console.warn('Could not parse map route config JSON', e);
        }

        if (!mapConfig || !mapConfig.routes || !mapConfig.routes.length) {
            mapConfig = {
                mode: 'custom_routes',
                entrance: { enabled: true, label: 'MAIN ENTRANCE', x: 48.8, y: 95.0 },
                exit: { enabled: false, label: 'ESTATE EXIT', x: 85.0, y: 92.0 },
                custom_waypoints: [{ id: 'wp_forest_bridge', label: 'Forest Footbridge', x: 42.0, y: 44.0 }],
                routes: [
                    {
                        id: 'route_main_promenade',
                        name: 'Main Sanctuary Walking Trail',
                        color: '#D4AF37',
                        stroke_type: 'dashed',
                        line_width: 3.2,
                        is_closed: false,
                        nodes: [
                            { type: 'entrance', curve: 'straight', curve_offset: 0 },
                            { type: 'spot_num', spot_number: 1, curve: 'arch_right', curve_offset: 16 },
                            { type: 'spot_num', spot_number: 2, curve: 'arch_left', curve_offset: -14 },
                            { type: 'spot_num', spot_number: 6, curve: 'arch_right', curve_offset: 22 },
                            { type: 'spot_num', spot_number: 7, curve: 'arch_left', curve_offset: -18 },
                            { type: 'spot_num', spot_number: 5, curve: 'arch_right', curve_offset: 16 },
                            { type: 'spot_num', spot_number: 4, curve: 'straight', curve_offset: 0 },
                            { type: 'spot_num', spot_number: 3, curve: 'arch_left', curve_offset: -16 }
                        ]
                    }
                ]
            };
        }

        window.adminMapConfig = mapConfig;
        var activeTool = 'select'; // 'select' | 'pencil'
        var activeRouteIdx = 0;
        var draggingTarget = null;

        var feedbackPill = document.getElementById('admin-map-drag-feedback');
        var feedbackText = document.getElementById('admin-map-drag-text');
        var pencilGuideLine = document.getElementById('admin-pencil-guide-line');

        // Math: Build SVG path segment string
        function buildSvgSegment(x1, y1, x2, y2, curveMode, curveOffset) {
            if (curveMode === 'straight' || (!curveMode && !curveOffset)) {
                return 'L ' + x2.toFixed(1) + ',' + y2.toFixed(1);
            }
            var dx = x2 - x1;
            var dy = y2 - y1;
            var dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < 0.1) return 'L ' + x2.toFixed(1) + ',' + y2.toFixed(1);

            var nx = -dy / dist;
            var ny = dx / dist;
            var midX = (x1 + x2) / 2.0;
            var midY = (y1 + y2) / 2.0;

            var offset = 0.0;
            if (typeof curveOffset === 'number' && curveOffset !== 0) {
                offset = curveOffset;
            } else if (curveMode === 'arch_right' || curveMode === 'curve_right') {
                offset = Math.min(55.0, Math.max(14.0, dist * 0.22));
            } else if (curveMode === 'arch_left' || curveMode === 'curve_left') {
                offset = -Math.min(55.0, Math.max(14.0, dist * 0.22));
            } else if (curveMode === 'curve') {
                offset = Math.min(40.0, Math.max(12.0, dist * 0.18));
            }

            var cx = midX + nx * offset;
            var cy = midY + ny * offset;
            return 'Q ' + cx.toFixed(1) + ',' + cy.toFixed(1) + ' ' + x2.toFixed(1) + ',' + y2.toFixed(1);
        }

        function resolveNodeCoords(node) {
            var ntype = node.type;
            if (ntype === 'entrance') {
                var ent = mapConfig.entrance || { x: 48.8, y: 95.0, label: 'MAIN ENTRANCE' };
                return { x: ent.x, y: ent.y, svg_x: (ent.x / 100) * 800, svg_y: (ent.y / 100) * 520, label: ent.label || 'Main Entrance', badge: 'ENTRANCE', icon: 'fa-solid fa-door-open', color: '#10B981' };
            }
            if (ntype === 'exit') {
                var ext = mapConfig.exit || { x: 85.0, y: 92.0, label: 'ESTATE EXIT' };
                return { x: ext.x, y: ext.y, svg_x: (ext.x / 100) * 800, svg_y: (ext.y / 100) * 520, label: ext.label || 'Estate Exit', badge: 'EXIT', icon: 'fa-solid fa-door-closed', color: '#E67E22' };
            }
            if (ntype === 'waypoint') {
                var wps = mapConfig.custom_waypoints || [];
                var wp = wps.find(function(w) { return w.id === node.waypoint_id; }) || node;
                var wx = parseFloat(wp.x || 50), wy = parseFloat(wp.y || 50);
                return { x: wx, y: wy, svg_x: (wx / 100) * 800, svg_y: (wy / 100) * 520, label: wp.label || 'Waypoint', badge: 'WAYPOINT', icon: 'fa-solid fa-location-dot', color: '#56C2C9' };
            }
            if (ntype === 'spot' || ntype === 'spot_id' || ntype === 'spot_num') {
                var pins = Array.from(document.querySelectorAll('.admin-master-pin'));
                var matchPin = pins.find(function(p) {
                    if (node.spot_number) return parseInt(p.getAttribute('data-spot-num')) === parseInt(node.spot_number);
                    if (node.spot_id) return parseInt(p.getAttribute('data-spot-id')) === parseInt(node.spot_id);
                    return false;
                });
                if (matchPin) {
                    var sx = parseFloat(matchPin.style.left) || 50;
                    var sy = parseFloat(matchPin.style.top) || 50;
                    var sTitle = matchPin.getAttribute('data-title') || 'Spot';
                    var sIcon = matchPin.getAttribute('data-icon') || 'fa-solid fa-house-chimney';
                    var sColor = matchPin.getAttribute('data-color') || '#D4AF37';
                    return { x: sx, y: sy, svg_x: (sx / 100) * 800, svg_y: (sy / 100) * 520, label: sTitle, badge: 'PROPERTY', icon: sIcon, color: sColor };
                }
            }
            return null;
        }

        // Recompile & Render all SVG routes live
        function updateAdminTrail() {
            var group = document.getElementById('admin-master-routes-group');
            if (!group) return;

            group.innerHTML = '';
            var routes = mapConfig.routes || [];

            routes.forEach(function(r, ridx) {
                var nodes = r.nodes || [];
                var resolved = [];
                nodes.forEach(function(n) {
                    var pt = resolveNodeCoords(n);
                    if (pt) {
                        resolved.push({ pt: pt, node: n });
                    }
                });

                if (resolved.length < 2) return;

                var svgD = 'M ' + resolved[0].pt.svg_x.toFixed(1) + ',' + resolved[0].pt.svg_y.toFixed(1);
                for (var i = 0; i < resolved.length - 1; i++) {
                    var curr = resolved[i];
                    var next = resolved[i + 1];
                    var curve = next.node.curve || 'straight';
                    var offset = parseFloat(next.node.curve_offset || 0);
                    svgD += ' ' + buildSvgSegment(curr.pt.svg_x, curr.pt.svg_y, next.pt.svg_x, next.pt.svg_y, curve, offset);
                }

                if (r.is_closed && resolved.length > 2) {
                    var curr = resolved[resolved.length - 1];
                    var next = resolved[0];
                    var curve = next.node.curve || 'straight';
                    var offset = parseFloat(next.node.curve_offset || 0);
                    svgD += ' ' + buildSvgSegment(curr.pt.svg_x, curr.pt.svg_y, next.pt.svg_x, next.pt.svg_y, curve, offset);
                    svgD += ' Z';
                }

                var col = r.color || '#D4AF37';
                var width = parseFloat(r.line_width || 3.2);
                var dash = (r.stroke_type === 'solid') ? 'none' : ((r.stroke_type === 'dotted') ? '3,4' : '9,6');

                // Aura path
                var aura = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                aura.setAttribute('id', 'admin-route-aura-' + ridx);
                aura.setAttribute('d', svgD);
                aura.setAttribute('fill', 'none');
                aura.setAttribute('stroke', col);
                aura.setAttribute('stroke-opacity', '0.32');
                aura.setAttribute('stroke-width', (width * 2.8).toFixed(1));
                aura.setAttribute('stroke-linecap', 'round');
                aura.setAttribute('stroke-linejoin', 'round');
                aura.setAttribute('filter', 'url(#admin-map-glow)');
                group.appendChild(aura);

                // Golden / Accent Line
                var line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                line.setAttribute('id', 'admin-route-line-' + ridx);
                line.setAttribute('d', svgD);
                line.setAttribute('fill', 'none');
                line.setAttribute('stroke', col);
                line.setAttribute('stroke-width', width.toFixed(1));
                line.setAttribute('stroke-dasharray', dash);
                line.setAttribute('stroke-linecap', 'round');
                line.setAttribute('stroke-linejoin', 'round');
                group.appendChild(line);
            });

            syncMapConfigToInput();
            renderRouteChainUI();
        }
        window.updateAdminRouteTrail = updateAdminTrail;

        function syncMapConfigToInput() {
            if (routeDataInput) {
                routeDataInput.value = JSON.stringify(mapConfig);
            }
        }

        // Render the sequence chain pills below the map with prominent delete buttons
        function renderRouteChainUI() {
            var chainBox = document.getElementById('admin-node-chain-list');
            if (!chainBox) return;

            var r = (mapConfig.routes && mapConfig.routes[activeRouteIdx]) ? mapConfig.routes[activeRouteIdx] : null;
            if (!r) {
                chainBox.innerHTML = '<span style="font-size:12px; color:var(--adm-text-muted);">No active pathway. Click "+ New Branch" to start.</span>';
                return;
            }

            // Sync route property inputs
            var nameInput = document.getElementById('route_name_input');
            var colInput = document.getElementById('route_color_input');
            var strokeInput = document.getElementById('route_stroke_input');
            var closedInput = document.getElementById('route_closed_input');
            if (nameInput) nameInput.value = r.name || 'Route 1';
            if (colInput) colInput.value = r.color || '#D4AF37';
            if (strokeInput) strokeInput.value = r.stroke_type || 'dashed';
            if (closedInput) closedInput.checked = !!r.is_closed;

            var nodes = r.nodes || [];
            if (!nodes.length) {
                chainBox.innerHTML = '<span style="font-size:12px; color:var(--adm-text-muted);">Pathway is empty. Add landmarks using the dropdown above or click <strong>Draw Path (Pencil)</strong> on the map!</span>';
                return;
            }

            var html = '';
            nodes.forEach(function(n, nidx) {
                var pt = resolveNodeCoords(n);
                var label = pt ? pt.label : (n.type === 'entrance' ? 'Main Entrance' : (n.type === 'exit' ? 'Estate Exit' : 'Landmark'));
                var icon = (pt && pt.icon) ? pt.icon : 'fa-solid fa-map-pin';
                var color = (pt && pt.color) ? pt.color : '#D4AF37';

                var chipClass = 'admin-node-chip';
                if (n.type === 'entrance') chipClass += ' chip-entrance';
                if (n.type === 'exit') chipClass += ' chip-exit';
                if (n.type === 'waypoint') chipClass += ' chip-waypoint';

                html += '<div class="' + chipClass + '">';
                html += '<span style="display:inline-flex; align-items:center; gap:6px;">';
                html += '<i class="' + icon + '" style="color:' + color + '; font-size:12px;"></i>';
                html += '<strong style="color:#FFF;">' + (nidx + 1) + '. ' + label + '</strong>';
                html += '</span>';

                html += '<div style="display:inline-flex; align-items:center; gap:4px; margin-left:6px;">';
                if (nidx > 0) {
                    html += '<button type="button" class="admin-node-ctrl-btn" onclick="moveRouteNode(' + activeRouteIdx + ',' + nidx + ',-1)" title="Move earlier in pathway">▲</button>';
                }
                if (nidx < nodes.length - 1) {
                    html += '<button type="button" class="admin-node-ctrl-btn" onclick="moveRouteNode(' + activeRouteIdx + ',' + nidx + ',1)" title="Move later in pathway">▼</button>';
                }
                html += '<button type="button" class="admin-node-del-btn" onclick="removeRouteNode(' + activeRouteIdx + ',' + nidx + ')" title="Remove this point from pathway"><i class="fa-solid fa-trash-can"></i> Remove</button>';
                html += '</div>';
                html += '</div>';

                // Curve connector between this node and next node
                if (nidx < nodes.length - 1) {
                    var nextNode = nodes[nidx + 1];
                    var cOff = parseFloat(nextNode.curve_offset || 0);

                    var cIcon = 'fa-solid fa-minus';
                    var modeLabel = 'Straight';
                    if (cOff > 20) { cIcon = 'fa-solid fa-arrow-turn-down'; modeLabel = 'Deep Arch Right'; }
                    else if (cOff > 0) { cIcon = 'fa-solid fa-water'; modeLabel = 'Arch Right'; }
                    else if (cOff < -20) { cIcon = 'fa-solid fa-arrow-turn-up'; modeLabel = 'Deep Arch Left'; }
                    else if (cOff < 0) { cIcon = 'fa-solid fa-water'; modeLabel = 'Arch Left'; }

                    html += '<div class="admin-curve-connector" onclick="cycleSegmentCurve(' + activeRouteIdx + ',' + (nidx + 1) + ')" title="Click to adjust path curve">';
                    html += '<i class="' + cIcon + '"></i>';
                    html += '<span>' + modeLabel + '</span>';
                    html += '<i class="fa-solid fa-caret-down" style="font-size:8px; opacity:0.6;"></i>';
                    html += '</div>';
                }
            });

            chainBox.innerHTML = html;
        }

        // Global tool actions
        window.setStudioTool = function(tool) {
            activeTool = tool;
            var btnSel = document.getElementById('btn-tool-select');
            var btnPenc = document.getElementById('btn-tool-pencil');
            var btnFinish = document.getElementById('btn-tool-finish-pencil');
            var modeInd = document.getElementById('admin-map-mode-indicator');
            var modeText = document.getElementById('admin-map-mode-text');
            var pencilHud = document.getElementById('admin-pencil-hud');
            var pencilRouteName = document.getElementById('admin-pencil-route-name');

            var currentRoute = (mapConfig.routes && mapConfig.routes[activeRouteIdx]) ? mapConfig.routes[activeRouteIdx] : null;
            var rName = currentRoute ? (currentRoute.name || ('Route ' + (activeRouteIdx + 1))) : 'Pathway';
            var rColor = currentRoute ? (currentRoute.color || '#56C2C9') : '#56C2C9';

            if (tool === 'pencil') {
                if (btnPenc) btnPenc.classList.add('gold-active');
                if (btnSel) btnSel.classList.remove('active');
                if (btnFinish) btnFinish.style.display = 'inline-flex';
                canvas.classList.add('is-pencil-mode');
                if (pencilHud) {
                    pencilHud.style.display = 'flex';
                    if (pencilRouteName) {
                        pencilRouteName.textContent = rName;
                        pencilRouteName.style.color = rColor;
                    }
                }
                if (modeInd && modeText) {
                    modeInd.style.borderColor = rColor;
                    modeText.innerHTML = '<strong style="color:' + rColor + ';">🖊️ Drawing Active (' + rName + '):</strong> Click map to add pathway points. Click <strong>Finish Path</strong> when done!';
                }
            } else {
                if (btnPenc) btnPenc.classList.remove('gold-active');
                if (btnSel) btnSel.classList.add('active');
                if (btnFinish) btnFinish.style.display = 'none';
                canvas.classList.remove('is-pencil-mode');
                if (pencilHud) pencilHud.style.display = 'none';
                if (pencilGuideLine) pencilGuideLine.setAttribute('opacity', '0');
                if (modeInd && modeText) {
                    modeInd.style.borderColor = 'var(--adm-gold)';
                    modeText.innerHTML = '<strong style="color:var(--adm-gold);">👆 Select & Drag Mode:</strong> Move pins to adjust coordinates freely.';
                }
            }
        };

        window.finishPencilDrawing = function() {
            window.setStudioTool('select');
            updateAdminTrail();
            if (feedbackPill && feedbackText) {
                feedbackPill.style.display = 'inline-flex';
                feedbackText.textContent = '✔ Pathway drawing finalized!';
                setTimeout(function() {
                    feedbackPill.style.display = 'none';
                }, 2200);
            }
        };

        window.startDrawingMainRoad = function() {
            activeRouteIdx = 0;
            var selector = document.getElementById('admin-route-selector');
            if (selector) selector.value = 0;
            renderRouteChainUI();
            window.setStudioTool('pencil');
        };

        // Double click or Escape / Enter to finish path drawing
        document.addEventListener('keydown', function(e) {
            if (activeTool === 'pencil' && (e.key === 'Escape' || e.key === 'Enter')) {
                window.finishPencilDrawing();
            }
        });

        if (canvas) {
            canvas.addEventListener('dblclick', function(e) {
                if (activeTool === 'pencil') {
                    e.preventDefault();
                    window.finishPencilDrawing();
                }
            });
        }

        window.cycleSegmentCurve = function(rIdx, nodeIdx) {
            var r = mapConfig.routes[rIdx];
            if (!r || !r.nodes || !r.nodes[nodeIdx]) return;

            var currOffset = parseFloat(r.nodes[nodeIdx].curve_offset || 0);
            var nextOffset = 0;
            var nextMode = 'straight';

            if (currOffset === 0) {
                nextOffset = 18;
                nextMode = 'arch_right';
            } else if (currOffset === 18) {
                nextOffset = -18;
                nextMode = 'arch_left';
            } else if (currOffset === -18) {
                nextOffset = 36;
                nextMode = 'arch_right';
            } else if (currOffset === 36) {
                nextOffset = -36;
                nextMode = 'arch_left';
            } else {
                nextOffset = 0;
                nextMode = 'straight';
            }

            r.nodes[nodeIdx].curve = nextMode;
            r.nodes[nodeIdx].curve_offset = nextOffset;
            updateAdminTrail();
        };

        window.moveRouteNode = function(rIdx, nIdx, dir) {
            var r = mapConfig.routes[rIdx];
            if (!r || !r.nodes) return;
            var targetIdx = nIdx + dir;
            if (targetIdx < 0 || targetIdx >= r.nodes.length) return;

            var temp = r.nodes[nIdx];
            r.nodes[nIdx] = r.nodes[targetIdx];
            r.nodes[targetIdx] = temp;
            updateAdminTrail();
        };

        window.removeRouteNode = function(rIdx, nIdx) {
            var r = mapConfig.routes[rIdx];
            if (!r || !r.nodes) return;
            r.nodes.splice(nIdx, 1);
            updateAdminTrail();
        };

        window.onSelectActiveRoute = function(val) {
            activeRouteIdx = parseInt(val) || 0;
            renderRouteChainUI();
            if (activeTool === 'pencil') {
                window.setStudioTool('pencil');
            }
        };

        window.addNewRouteBranch = function() {
            var newId = 'route_' + (mapConfig.routes.length + 1);
            var colors = ['#06B6D4', '#2ECC71', '#F59E0B', '#E74C3C', '#9B59B6', '#D4AF37'];
            var col = colors[mapConfig.routes.length % colors.length];

            mapConfig.routes.push({
                id: newId,
                name: 'Sub-Branch Road ' + (mapConfig.routes.length + 1),
                color: col,
                stroke_type: 'solid',
                line_width: 3.0,
                is_closed: false,
                nodes: []
            });

            activeRouteIdx = mapConfig.routes.length - 1;
            var selector = document.getElementById('admin-route-selector');
            if (selector) {
                var opt = document.createElement('option');
                opt.value = activeRouteIdx;
                opt.textContent = 'Sub-Branch Road ' + (activeRouteIdx + 1);
                selector.appendChild(opt);
                selector.value = activeRouteIdx;
            }

            var routeCount = document.getElementById('admin-map-route-count-text');
            if (routeCount) routeCount.textContent = mapConfig.routes.length + ' Custom Pathway(s) Connected';

            updateAdminTrail();
            window.setStudioTool('pencil');
        };

        window.deleteActiveRoute = function() {
            if (mapConfig.routes.length <= 1) {
                alert('Cannot delete the last remaining master route.');
                return;
            }
            mapConfig.routes.splice(activeRouteIdx, 1);
            activeRouteIdx = Math.max(0, activeRouteIdx - 1);

            var selector = document.getElementById('admin-route-selector');
            if (selector) {
                selector.innerHTML = '';
                mapConfig.routes.forEach(function(r, idx) {
                    var opt = document.createElement('option');
                    opt.value = idx;
                    opt.textContent = r.name || ('Route ' + (idx + 1));
                    selector.appendChild(opt);
                });
                selector.value = activeRouteIdx;
            }

            var routeCount = document.getElementById('admin-map-route-count-text');
            if (routeCount) routeCount.textContent = mapConfig.routes.length + ' Custom Pathway(s) Connected';

            updateAdminTrail();
        };

        window.clearActiveRoutePoints = function() {
            var r = mapConfig.routes && mapConfig.routes[activeRouteIdx];
            if (!r || !r.nodes || !r.nodes.length) {
                alert('Pathway is already empty.');
                return;
            }
            if (confirm('Are you sure you want to clear all points from this pathway?')) {
                r.nodes = [];
                updateAdminTrail();
            }
        };

        window.openQuickPropertyModal = function(x, y) {
            var modal = document.getElementById('modal-quick-add-property');
            if (!modal) return;
            var inX = document.getElementById('qp_spot_x');
            var inY = document.getElementById('qp_spot_y');
            if (inX) inX.value = (typeof x === 'number') ? x : 50;
            if (inY) inY.value = (typeof y === 'number') ? y : 50;
            modal.style.display = 'flex';
        };

        window.closeQuickPropertyModal = function() {
            var modal = document.getElementById('modal-quick-add-property');
            if (modal) modal.style.display = 'none';
        };

        window.onQuickPropPresetChange = function(selectEl) {
            if (!selectEl) return;
            var opt = selectEl.options[selectEl.selectedIndex];
            if (!opt) return;

            var icon = opt.getAttribute('data-icon') || 'fa-solid fa-house-chimney';
            var color = opt.getAttribute('data-color') || '#10B981';
            var cat = opt.getAttribute('data-cat') || 'stays';
            var title = opt.getAttribute('data-title') || '';
            var desc = opt.getAttribute('data-desc') || '';
            var price = opt.getAttribute('data-price') || '';

            var inIcon = document.getElementById('qp_icon_class');
            var inColor = document.getElementById('qp_pin_color');
            var inTitle = document.getElementById('qp_title');
            var inCat = document.getElementById('qp_category');
            var inDesc = document.getElementById('qp_desc');
            var inPrice = document.getElementById('qp_stay_price');

            if (inIcon) inIcon.value = icon;
            if (inColor) inColor.value = color;
            if (inTitle && title) inTitle.value = title;
            if (inCat && cat) inCat.value = cat;
            if (inDesc && desc) inDesc.value = desc;
            if (inPrice) inPrice.value = price;
        };

        window.updateActiveRouteProp = function(prop, val) {
            var r = mapConfig.routes[activeRouteIdx];
            if (!r) return;
            r[prop] = val;
            updateAdminTrail();
        };

        window.toggleEntranceGate = function() {
            if (!mapConfig.entrance) mapConfig.entrance = { enabled: true, label: 'MAIN ENTRANCE', x: 48.8, y: 95.0 };
            mapConfig.entrance.enabled = !mapConfig.entrance.enabled;
            var pin = document.getElementById('admin-entrance-pin');
            if (pin) pin.style.display = mapConfig.entrance.enabled ? 'flex' : 'none';
            updateAdminTrail();
        };

        window.toggleExitGate = function() {
            if (!mapConfig.exit) mapConfig.exit = { enabled: false, label: 'ESTATE EXIT', x: 85.0, y: 92.0 };
            mapConfig.exit.enabled = !mapConfig.exit.enabled;
            var pin = document.getElementById('admin-exit-pin');
            if (pin) pin.style.display = mapConfig.exit.enabled ? 'flex' : 'none';
            var btn = document.getElementById('btn-toggle-exit');
            if (btn) btn.innerHTML = '<i class="fa-solid fa-door-closed" style="color:#E67E22;"></i> <span>Exit Gate (' + (mapConfig.exit.enabled ? 'ON' : 'OFF') + ')</span>';
            updateAdminTrail();
        };

        window.openAddWaypointModal = function() {
            var modal = document.getElementById('modal-add-waypoint');
            var input = document.getElementById('modal_wp_label_input');
            if (modal) {
                if (input) {
                    input.value = 'Scenic Viewpoint';
                    setTimeout(function() { input.focus(); input.select(); }, 50);
                }
                modal.style.display = 'flex';
            }
        };

        window.closeAddWaypointModal = function() {
            var modal = document.getElementById('modal-add-waypoint');
            if (modal) modal.style.display = 'none';
        };

        window.setWaypointLabelPreset = function(name) {
            var input = document.getElementById('modal_wp_label_input');
            if (input && name) {
                input.value = name;
                input.focus();
            }
        };

        window.submitAddWaypointModal = function() {
            var input = document.getElementById('modal_wp_label_input');
            var label = input ? input.value : '';
            if (!label || !label.trim()) {
                alert('Please provide a name or label for the waypoint.');
                if (input) input.focus();
                return;
            }
            window.addCustomWaypointDirect(label.trim(), true);
            window.closeAddWaypointModal();
        };

        window.submitInlineAddWaypoint = function() {
            var input = document.getElementById('inline-new-waypoint-name');
            var label = input ? input.value : '';
            if (!label || !label.trim()) {
                alert('Please enter a waypoint label to add.');
                if (input) input.focus();
                return;
            }
            window.addCustomWaypointDirect(label.trim(), true);
            input.value = '';
            window.openManageWaypointsModal(); // refresh list
        };

        window.addCustomWaypointDirect = function(label, addToRoute) {
            if (!label || !label.trim()) return;

            var newWp = {
                id: 'wp_' + Date.now(),
                label: label.trim(),
                x: 50.0,
                y: 50.0
            };

            if (!mapConfig.custom_waypoints) mapConfig.custom_waypoints = [];
            mapConfig.custom_waypoints.push(newWp);

            // Create waypoint DOM element
            var container = document.getElementById('admin-waypoints-container');
            if (container) {
                var el = document.createElement('div');
                el.className = 'admin-waypoint-pin';
                el.id = 'admin-waypoint-' + newWp.id;
                el.setAttribute('data-wpid', newWp.id);
                el.style.left = newWp.x + '%';
                el.style.top = newWp.y + '%';
                el.style.pointerEvents = 'auto';
                el.innerHTML = '<div class="admin-pin-pulse" style="background:rgba(86,194,201,0.3);"></div><div class="admin-pin-core" style="background:#081d1a;border-color:#56C2C9;color:#56C2C9;width:26px;height:26px;font-size:10px;"><i class="fa-solid fa-location-dot"></i></div><div class="admin-waypoint-badge"><span>' + newWp.label + '</span><button type="button" class="admin-waypoint-del-btn" onclick="deleteCustomWaypoint(\'' + newWp.id + '\', \'' + newWp.label.replace(/'/g, "\\'") + '\', event);" title="Delete Waypoint">✕</button></div>';
                container.appendChild(el);
            }

            // Append to active route
            if (addToRoute && mapConfig.routes && mapConfig.routes[activeRouteIdx]) {
                if (!mapConfig.routes[activeRouteIdx].nodes) mapConfig.routes[activeRouteIdx].nodes = [];
                mapConfig.routes[activeRouteIdx].nodes.push({
                    type: 'waypoint',
                    waypoint_id: newWp.id,
                    curve: 'straight',
                    curve_offset: 0
                });
            }

            // Update waypoints count text
            var countEl = document.getElementById('admin-waypoint-count-text');
            if (countEl) countEl.textContent = mapConfig.custom_waypoints.length;

            updateAdminTrail();

            if (feedbackPill && feedbackText) {
                feedbackPill.style.display = 'inline-flex';
                feedbackText.textContent = 'Added waypoint: ' + newWp.label;
                setTimeout(function() {
                    feedbackPill.style.display = 'none';
                }, 2200);
            }
        };

        window.deleteCustomWaypoint = function(wpid, label, e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }
            if (!confirm('Are you sure you want to remove waypoint "' + (label || 'Waypoint') + '" from the map and pathways?')) {
                return;
            }

            // 1. Remove from mapConfig.custom_waypoints
            if (mapConfig.custom_waypoints) {
                mapConfig.custom_waypoints = mapConfig.custom_waypoints.filter(function(w) {
                    return w.id !== wpid;
                });
            }

            // 2. Remove from all routes
            if (mapConfig.routes) {
                mapConfig.routes.forEach(function(r) {
                    if (r.nodes) {
                        r.nodes = r.nodes.filter(function(n) {
                            return !(n.type === 'waypoint' && n.waypoint_id === wpid);
                        });
                    }
                });
            }

            // 3. Remove DOM pin element
            var el = document.getElementById('admin-waypoint-' + wpid);
            if (el) {
                el.remove();
            }

            // 4. Remove from quick_add_node_select dropdown
            var select = document.getElementById('quick_add_node_select');
            if (select) {
                var opt = select.querySelector('option[value="waypoint_' + wpid + '"]');
                if (opt) opt.remove();
            }

            // 5. Update waypoints count text
            var countEl = document.getElementById('admin-waypoint-count-text');
            if (countEl) countEl.textContent = (mapConfig.custom_waypoints || []).length;

            // 6. Refresh modal list if open
            if (document.getElementById('modal-manage-waypoints') && document.getElementById('modal-manage-waypoints').style.display !== 'none') {
                openManageWaypointsModal();
            }

            updateAdminTrail();

            if (feedbackPill && feedbackText) {
                feedbackPill.style.display = 'inline-flex';
                feedbackText.textContent = 'Removed waypoint: ' + (label || 'Waypoint');
                setTimeout(function() {
                    feedbackPill.style.display = 'none';
                }, 2000);
            }
        };

        window.openManageWaypointsModal = function() {
            var modal = document.getElementById('modal-manage-waypoints');
            var listBody = document.getElementById('admin-waypoint-list-body');
            if (!modal || !listBody) return;

            var wps = mapConfig.custom_waypoints || [];
            if (!wps.length) {
                listBody.innerHTML = '<div style="text-align:center; padding:24px; color:var(--adm-text-muted); font-size:12.5px;">No custom waypoints on the map. Enter a name above or click "+ Add New Waypoint" to create one.</div>';
            } else {
                var html = '';
                wps.forEach(function(wp, idx) {
                    var safeLabel = (wp.label || 'Waypoint ' + (idx + 1)).replace(/'/g, "\\'");
                    html += '<div style="display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.35); border:1px solid rgba(86,194,201,0.25); border-radius:8px; padding:10px 14px;">';
                    html += '<div style="display:flex; align-items:center; gap:10px;">';
                    html += '<span style="width:24px; height:24px; border-radius:50%; background:#081d1a; border:1px solid #56C2C9; color:#56C2C9; display:flex; align-items:center; justify-content:center; font-size:10px;"><i class="fa-solid fa-location-dot"></i></span>';
                    html += '<strong style="color:#FFFFFF; font-size:13px;">' + (wp.label || 'Waypoint') + '</strong>';
                    html += '</div>';
                    html += '<button type="button" class="adm-btn-action" onclick="deleteCustomWaypoint(\'' + wp.id + '\', \'' + safeLabel + '\', event);" style="padding:4px 10px; font-size:11px; background:rgba(231,76,60,0.2); color:#FF7675; border:1px solid rgba(231,76,60,0.4);" title="Delete this waypoint">';
                    html += '<i class="fa-solid fa-trash-can"></i> Remove';
                    html += '</button>';
                    html += '</div>';
                });
                listBody.innerHTML = html;
            }

            modal.style.display = 'flex';
        };

        window.closeManageWaypointsModal = function() {
            var modal = document.getElementById('modal-manage-waypoints');
            if (modal) modal.style.display = 'none';
        };

        window.resetToScenicLoop = function() {
            if (!confirm('Reset current route to the scenic connected walking loop?')) return;
            var pins = Array.from(document.querySelectorAll('.admin-master-pin'));
            var nodes = [{ type: 'entrance', curve: 'straight', curve_offset: 0 }];
            pins.forEach(function(p, pidx) {
                var sNum = parseInt(p.getAttribute('data-spot-num')) || (pidx + 1);
                var curve = (pidx % 2 === 0) ? 'arch_right' : 'arch_left';
                var offset = (pidx % 2 === 0) ? 16 : -16;
                nodes.push({
                    type: 'spot_num',
                    spot_number: sNum,
                    curve: curve,
                    curve_offset: offset
                });
            });
            mapConfig.routes[activeRouteIdx].nodes = nodes;
            mapConfig.routes[activeRouteIdx].is_closed = false;
            updateAdminTrail();
        };

        // Fast SVG-only update during active drag (avoids rebuilding bottom UI during mouse movement)
        function updateAdminTrailSvgOnly() {
            var group = document.getElementById('admin-master-routes-group');
            if (!group) return;

            group.innerHTML = '';
            var routes = mapConfig.routes || [];

            routes.forEach(function(r, ridx) {
                var nodes = r.nodes || [];
                var resolved = [];
                nodes.forEach(function(n) {
                    var pt = resolveNodeCoords(n);
                    if (pt) resolved.push({ pt: pt, node: n });
                });

                if (resolved.length < 2) return;

                var svgD = 'M ' + resolved[0].pt.svg_x.toFixed(1) + ',' + resolved[0].pt.svg_y.toFixed(1);
                for (var i = 0; i < resolved.length - 1; i++) {
                    var curr = resolved[i];
                    var next = resolved[i + 1];
                    var curve = next.node.curve || 'straight';
                    var offset = parseFloat(next.node.curve_offset || 0);
                    svgD += ' ' + buildSvgSegment(curr.pt.svg_x, curr.pt.svg_y, next.pt.svg_x, next.pt.svg_y, curve, offset);
                }

                if (r.is_closed && resolved.length > 2) {
                    var curr = resolved[resolved.length - 1];
                    var next = resolved[0];
                    var curve = next.node.curve || 'straight';
                    var offset = parseFloat(next.node.curve_offset || 0);
                    svgD += ' ' + buildSvgSegment(curr.pt.svg_x, curr.pt.svg_y, next.pt.svg_x, next.pt.svg_y, curve, offset);
                    svgD += ' Z';
                }

                var col = r.color || '#D4AF37';
                var width = parseFloat(r.line_width || 3.2);
                var dash = (r.stroke_type === 'solid') ? 'none' : ((r.stroke_type === 'dotted') ? '3,4' : '9,6');

                var aura = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                aura.setAttribute('id', 'admin-route-aura-' + ridx);
                aura.setAttribute('d', svgD);
                aura.setAttribute('fill', 'none');
                aura.setAttribute('stroke', col);
                aura.setAttribute('stroke-opacity', '0.32');
                aura.setAttribute('stroke-width', (width * 2.8).toFixed(1));
                aura.setAttribute('stroke-linecap', 'round');
                aura.setAttribute('stroke-linejoin', 'round');
                aura.setAttribute('filter', 'url(#admin-map-glow)');
                group.appendChild(aura);

                var line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                line.setAttribute('id', 'admin-route-line-' + ridx);
                line.setAttribute('d', svgD);
                line.setAttribute('fill', 'none');
                line.setAttribute('stroke', col);
                line.setAttribute('stroke-width', width.toFixed(1));
                line.setAttribute('stroke-dasharray', dash);
                line.setAttribute('stroke-linecap', 'round');
                line.setAttribute('stroke-linejoin', 'round');
                group.appendChild(line);
            });

            syncMapConfigToInput();
        }

        // Pointer / Drag and Pencil Click Handling
        function onPointerDown(e) {
            if (e.target.closest('.admin-waypoint-del-btn')) {
                return;
            }

            var pin = e.target.closest('.admin-master-pin, .admin-entrance-pin, .admin-exit-pin, .admin-waypoint-pin');

            // Pencil Mode Interaction
            if (activeTool === 'pencil') {
                e.preventDefault();
                var currentRoute = mapConfig.routes[activeRouteIdx];
                if (!currentRoute) return;

                if (pin) {
                    // Clicked an existing element
                    if (pin.classList.contains('admin-master-pin')) {
                        var sNum = parseInt(pin.getAttribute('data-spot-num')) || 1;
                        currentRoute.nodes.push({ type: 'spot_num', spot_number: sNum, curve: 'straight', curve_offset: 0 });
                    } else if (pin.classList.contains('admin-entrance-pin')) {
                        currentRoute.nodes.push({ type: 'entrance', curve: 'straight', curve_offset: 0 });
                    } else if (pin.classList.contains('admin-exit-pin')) {
                        currentRoute.nodes.push({ type: 'exit', curve: 'straight', curve_offset: 0 });
                    } else if (pin.classList.contains('admin-waypoint-pin')) {
                        var wpid = pin.getAttribute('data-wpid');
                        currentRoute.nodes.push({ type: 'waypoint', waypoint_id: wpid, curve: 'straight', curve_offset: 0 });
                    }
                    updateAdminTrail();
                } else if (e.target === canvas || e.target.closest('.admin-master-trail-svg') || e.target.id === 'admin-master-pins-layer') {
                    // Clicked empty terrain -> drop a new waypoint here!
                    var rect = canvas.getBoundingClientRect();
                    var clickX = e.clientX - rect.left;
                    var clickY = e.clientY - rect.top;
                    var pctX = Math.round(Math.max(2, Math.min(98, (clickX / rect.width) * 100)) * 10) / 10;
                    var pctY = Math.round(Math.max(2, Math.min(98, (clickY / rect.height) * 100)) * 10) / 10;

                    var newWp = {
                        id: 'wp_' + Date.now(),
                        label: 'Path Waypoint',
                        x: pctX,
                        y: pctY
                    };
                    if (!mapConfig.custom_waypoints) mapConfig.custom_waypoints = [];
                    mapConfig.custom_waypoints.push(newWp);

                    var container = document.getElementById('admin-waypoints-container');
                    if (container) {
                        var el = document.createElement('div');
                        el.className = 'admin-waypoint-pin';
                        el.id = 'admin-waypoint-' + newWp.id;
                        el.setAttribute('data-wpid', newWp.id);
                        el.style.left = newWp.x + '%';
                        el.style.top = newWp.y + '%';
                        el.style.pointerEvents = 'auto';
                        el.innerHTML = '<div class="admin-pin-pulse" style="background:rgba(86,194,201,0.3);"></div><div class="admin-pin-core" style="background:#081d1a;border-color:#56C2C9;color:#56C2C9;width:26px;height:26px;font-size:10px;"><i class="fa-solid fa-location-dot"></i></div><div class="admin-waypoint-badge"><span>' + newWp.label + '</span><button type="button" class="admin-waypoint-del-btn" onclick="deleteCustomWaypoint(\'' + newWp.id + '\', \'' + newWp.label.replace(/'/g, "\\'") + '\', event);" title="Delete Waypoint">✕</button></div>';
                        container.appendChild(el);
                    }

                    var countEl = document.getElementById('admin-waypoint-count-text');
                    if (countEl) countEl.textContent = mapConfig.custom_waypoints.length;

                    currentRoute.nodes.push({ type: 'waypoint', waypoint_id: newWp.id, curve: 'straight', curve_offset: 0 });
                    updateAdminTrail();
                }
                return;
            }

            // Select & Drag Mode
            if (!pin) return;
            e.preventDefault();

            if (pin.classList.contains('admin-master-pin')) {
                draggingTarget = { type: 'spot', el: pin, idx: pin.getAttribute('data-idx') };
            } else if (pin.classList.contains('admin-entrance-pin')) {
                draggingTarget = { type: 'entrance', el: pin };
            } else if (pin.classList.contains('admin-exit-pin')) {
                draggingTarget = { type: 'exit', el: pin };
            } else if (pin.classList.contains('admin-waypoint-pin')) {
                draggingTarget = { type: 'waypoint', el: pin, id: pin.getAttribute('data-wpid') };
            }

            if (draggingTarget && draggingTarget.el) {
                draggingTarget.el.classList.add('is-dragging');
            }

            if (feedbackPill && feedbackText) {
                feedbackPill.style.display = 'inline-flex';
                feedbackText.textContent = 'Dragging ' + (draggingTarget ? draggingTarget.type.toUpperCase() : 'pin') + '...';
            }

            document.addEventListener('mousemove', onPointerMove, { passive: false });
            document.addEventListener('mouseup', onPointerUp);
            document.addEventListener('touchmove', onPointerMove, { passive: false });
            document.addEventListener('touchend', onPointerUp);
        }

        function onPointerMove(e) {
            if (!draggingTarget) {
                // In pencil mode, draw guide rubberband
                if (activeTool === 'pencil' && pencilGuideLine) {
                    var r = mapConfig.routes[activeRouteIdx];
                    if (r && r.nodes && r.nodes.length > 0) {
                        var lastPt = resolveNodeCoords(r.nodes[r.nodes.length - 1]);
                        if (lastPt) {
                            var rect = canvas.getBoundingClientRect();
                            var mx = ((e.clientX - rect.left) / rect.width) * 800;
                            var my = ((e.clientY - rect.top) / rect.height) * 520;
                            pencilGuideLine.setAttribute('x1', lastPt.svg_x);
                            pencilGuideLine.setAttribute('y1', lastPt.svg_y);
                            pencilGuideLine.setAttribute('x2', mx);
                            pencilGuideLine.setAttribute('y2', my);
                            pencilGuideLine.setAttribute('opacity', '0.85');
                        }
                    }
                }
                return;
            }
            if (e.cancelable) e.preventDefault();

            var clientX = e.clientX;
            var clientY = e.clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }

            var rect = canvas.getBoundingClientRect();
            var relX = clientX - rect.left;
            var relY = clientY - rect.top;

            var pctX = Math.round(Math.max(2, Math.min(98, (relX / rect.width) * 100)) * 10) / 10;
            var pctY = Math.round(Math.max(2, Math.min(98, (relY / rect.height) * 100)) * 10) / 10;

            draggingTarget.el.style.left = pctX + '%';
            draggingTarget.el.style.top = pctY + '%';

            if (draggingTarget.type === 'spot') {
                var sIdx = draggingTarget.idx;
                var inputX = document.getElementById('spot_x_' + sIdx);
                var inputY = document.getElementById('spot_y_' + sIdx);
                if (inputX) inputX.value = pctX;
                if (inputY) inputY.value = pctY;

                if (feedbackText) {
                    var sTitle = draggingTarget.el.getAttribute('data-title') || 'Property';
                    feedbackText.textContent = 'Moving ' + sTitle;
                }
            } else if (draggingTarget.type === 'entrance') {
                if (!mapConfig.entrance) mapConfig.entrance = { enabled: true, label: 'MAIN ENTRANCE' };
                mapConfig.entrance.x = pctX;
                mapConfig.entrance.y = pctY;
                if (feedbackText) feedbackText.textContent = 'Moving Main Entrance';
            } else if (draggingTarget.type === 'exit') {
                if (!mapConfig.exit) mapConfig.exit = { enabled: true, label: 'ESTATE EXIT' };
                mapConfig.exit.x = pctX;
                mapConfig.exit.y = pctY;
                if (feedbackText) feedbackText.textContent = 'Moving Estate Exit';
            } else if (draggingTarget.type === 'waypoint') {
                var wp = (mapConfig.custom_waypoints || []).find(function(w) { return w.id === draggingTarget.id; });
                if (wp) {
                    wp.x = pctX;
                    wp.y = pctY;
                }
                if (feedbackText) feedbackText.textContent = 'Moving Waypoint';
            }

            updateAdminTrailSvgOnly();
        }

        function onPointerUp() {
            if (!draggingTarget) return;
            draggingTarget.el.classList.remove('is-dragging');
            draggingTarget = null;

            if (feedbackPill) {
                setTimeout(function() {
                    if (!draggingTarget) feedbackPill.style.display = 'none';
                }, 1400);
            }

            document.removeEventListener('mousemove', onPointerMove);
            document.removeEventListener('mouseup', onPointerUp);
            document.removeEventListener('touchmove', onPointerMove);
            document.removeEventListener('touchend', onPointerUp);

            updateAdminTrail();
        }

        canvas.removeEventListener('mousedown', onPointerDown);
        canvas.addEventListener('mousedown', onPointerDown);
        canvas.removeEventListener('touchstart', onPointerDown);
        canvas.addEventListener('touchstart', onPointerDown, { passive: false });
        canvas.addEventListener('mousemove', onPointerMove);

        // Initial compile & render
        updateAdminTrail();

        window.focusPinOnMasterMap = function(idx) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;

            var studio = document.querySelector('.admin-map-studio-card');
            if (studio) {
                try {
                    studio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch(e) {}
            }

            pin.classList.remove('is-highlighted');
            void pin.offsetWidth;
            pin.classList.add('is-highlighted');

            setTimeout(function() {
                pin.classList.remove('is-highlighted');
            }, 3600);
        };

        window.addSelectedNodeToActiveRoute = function() {
            var select = document.getElementById('quick_add_node_select');
            if (!select || !mapConfig || !mapConfig.routes || !mapConfig.routes[activeRouteIdx]) return;
            var val = select.value;
            if (!val) return;

            var r = mapConfig.routes[activeRouteIdx];
            if (!r.nodes) r.nodes = [];

            if (val === 'entrance') {
                r.nodes.push({ type: 'entrance', curve: 'straight', curve_offset: 0 });
            } else if (val === 'exit') {
                r.nodes.push({ type: 'exit', curve: 'straight', curve_offset: 0 });
            } else if (val.indexOf('waypoint_') === 0) {
                var wpid = val.replace('waypoint_', '');
                r.nodes.push({ type: 'waypoint', waypoint_id: wpid, curve: 'straight', curve_offset: 0 });
            } else if (val.indexOf('spot_') === 0) {
                var snum = parseInt(val.replace('spot_', ''), 10) || 1;
                var curve = (r.nodes.length % 2 === 0) ? 'arch_right' : 'arch_left';
                var offset = (r.nodes.length % 2 === 0) ? 16 : -16;
                r.nodes.push({ type: 'spot_num', spot_number: snum, curve: curve, curve_offset: offset });
            }

            updateAdminTrail();
        };

        window.autoConnectAllSpotsToRoute = function() {
            if (!mapConfig || !mapConfig.routes || !mapConfig.routes[activeRouteIdx]) return;
            var pins = Array.from(document.querySelectorAll('.admin-master-pin'));
            if (!pins.length) {
                alert('No registered spots found to connect.');
                return;
            }

            // Sort pins by spot number
            pins.sort(function(a, b) {
                var numA = parseInt(a.getAttribute('data-spot-num')) || 0;
                var numB = parseInt(b.getAttribute('data-spot-num')) || 0;
                return numA - numB;
            });

            var newNodes = [{ type: 'entrance', curve: 'straight', curve_offset: 0 }];
            pins.forEach(function(p, pidx) {
                var sNum = parseInt(p.getAttribute('data-spot-num')) || (pidx + 1);
                var curve = (pidx % 2 === 0) ? 'arch_right' : 'arch_left';
                var offset = (pidx % 2 === 0) ? 18 : -18;
                newNodes.push({
                    type: 'spot_num',
                    spot_number: sNum,
                    curve: curve,
                    curve_offset: offset
                });
            });

            mapConfig.routes[activeRouteIdx].nodes = newNodes;
            updateAdminTrail();
        };

        window.applySpotPreset = function(selectEl, target) {
            if (!selectEl) return;
            var opt = selectEl.options[selectEl.selectedIndex];
            if (!opt || opt.value === 'custom') return;

            var icon = opt.getAttribute('data-icon') || 'fa-solid fa-tree';
            var color = opt.getAttribute('data-color') || '#10B981';
            var cat = opt.getAttribute('data-cat') || 'stays';
            var struct = opt.getAttribute('data-struct') || 'single_hut';

            if (target === 'new') {
                var iconInp = document.getElementById('new_spot_icon_class');
                var colorInp = document.getElementById('new_spot_pin_color');
                var catInp = document.getElementById('new_spot_category');
                var structInp = document.getElementById('new_spot_structure_type');
                if (iconInp) iconInp.value = icon;
                if (colorInp) colorInp.value = color;
                if (catInp) catInp.value = cat;
                if (structInp) structInp.value = struct;
            } else {
                var idx = parseInt(target, 10);
                var iconInp = document.getElementById('spot_icon_class_' + idx);
                var colorInp = document.getElementById('spot_pin_color_' + idx);
                var catInp = document.getElementById('spot_category_' + idx);
                var structInp = document.getElementById('spot_structure_type_' + idx);
                if (iconInp) { iconInp.value = icon; syncSpotIconToPin(idx, icon); }
                if (colorInp) { colorInp.value = color; syncSpotColorToPin(idx, color); }
                if (catInp) catInp.value = cat;
                if (structInp) structInp.value = struct;
            }
        };

        window.syncSpotIconToPin = function(idx, val) {
            val = (val || '').trim() || 'fa-solid fa-location-dot';
            var pin = document.getElementById('master-pin-' + idx);
            if (pin) {
                pin.setAttribute('data-icon', val);
                var pinIcon = document.getElementById('pin-icon-' + idx);
                if (pinIcon) pinIcon.className = val;
            }
            var cardBadge = document.getElementById('card-icon-badge-' + idx);
            if (cardBadge) {
                var badgeI = cardBadge.querySelector('i');
                if (badgeI) badgeI.className = val;
            }
        };

        window.syncSpotColorToPin = function(idx, val) {
            val = (val || '').trim() || '#10B981';
            var pin = document.getElementById('master-pin-' + idx);
            if (pin) {
                pin.setAttribute('data-color', val);
                var pulse = document.getElementById('pin-pulse-' + idx);
                if (pulse) pulse.style.background = val;
                var core = document.getElementById('pin-core-' + idx);
                if (core) {
                    core.style.borderColor = val;
                    core.style.color = val;
                    core.style.boxShadow = '0 0 12px ' + val + '55';
                }
                var lbl = pin.querySelector('.admin-pin-label');
                if (lbl) lbl.style.borderLeft = '2px solid ' + val;
            }

            var cardSpotNum = document.getElementById('card-spot-num-badge-' + idx);
            if (cardSpotNum) cardSpotNum.style.background = val;
            var cardColTxt = document.getElementById('card-color-text-' + idx);
            if (cardColTxt) cardColTxt.textContent = val;
            var cardIconBadge = document.getElementById('card-icon-badge-' + idx);
            if (cardIconBadge) cardIconBadge.style.background = val;
        };

        window.syncSpotNumToPin = function(idx, val) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;
            val = parseInt(val, 10) || 1;
            var numStr = (val < 10 ? '0' : '') + val;
            pin.setAttribute('data-spot-num', val);
            var miniNum = document.getElementById('pin-num-' + idx);
            if (miniNum) miniNum.textContent = numStr;
            var cardNum = document.getElementById('card-spot-num-badge-' + idx);
            if (cardNum) cardNum.textContent = '#' + numStr;
            updateAdminTrail();
        };

        window.syncSpotTitleToPin = function(idx, val) {
            var pin = document.getElementById('master-pin-' + idx);
            if (!pin) return;
            pin.setAttribute('data-title', val);
            var lblStrong = pin.querySelector('.admin-pin-label strong');
            if (lblStrong) lblStrong.textContent = (val || '').substring(0, 20);
            var cardTitle = document.getElementById('card-spot-title-text-' + idx);
            if (cardTitle) cardTitle.textContent = val;
        };
    }
    window.initAdminMasterMapStudio = initAdminMasterMapStudio;

    // Auto initialize on DOM readiness
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAdminMasterMapStudio);
    } else {
        setTimeout(initAdminMasterMapStudio, 50);
    }

    function removeSpotPhotoThumbnail(btn) {
        if (!btn) return;
        var item = btn.closest('.adm-spot-photo-thumb');
        if (item) {
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            setTimeout(function() {
                item.remove();
            }, 150);
        }
    }
    window.removeSpotPhotoThumbnail = removeSpotPhotoThumbnail;

    window.confirmDeleteAllSpots = function() {
        if (!confirm('Are you sure you want to delete ALL registered sanctuary spots from the map and database? This action cannot be undone.')) {
            return;
        }
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit_section.php?section=sanctuary_map';

        var csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) {
            var inCsrf = document.createElement('input');
            inCsrf.type = 'hidden';
            inCsrf.name = 'csrf_token';
            inCsrf.value = csrf.value;
            form.appendChild(inCsrf);
        }

        var inType = document.createElement('input');
        inType.type = 'hidden';
        inType.name = 'form_type';
        inType.value = 'sanctuary_map_settings';
        form.appendChild(inType);

        var inDelAll = document.createElement('input');
        inDelAll.type = 'hidden';
        inDelAll.name = 'delete_all_spots';
        inDelAll.value = '1';
        form.appendChild(inDelAll);

        document.body.appendChild(form);
        form.submit();
    };

    function previewMultiSpotUpload(input, previewContainerId) {
        var container = document.getElementById(previewContainerId);
        if (!container || !input || !input.files) return;
        container.innerHTML = '';
        var count = input.files.length;
        if (count === 0) return;
        
        var info = document.createElement('div');
        info.style.cssText = 'font-size: 11px; color: #2ecc71; margin-bottom: 6px; font-weight: 600;';
        info.textContent = '✓ ' + count + ' new photo' + (count > 1 ? 's' : '') + ' selected';
        container.appendChild(info);

        var grid = document.createElement('div');
        grid.style.cssText = 'display: flex; gap: 8px; flex-wrap: wrap; align-items: center;';
        
        Array.from(input.files).forEach(function(file) {
            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var thumb = document.createElement('div');
                    thumb.style.cssText = 'width: 55px; height: 55px; border-radius: 6px; overflow: hidden; border: 1px solid rgba(46, 204, 113, 0.4); position: relative;';
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                    thumb.appendChild(img);
                    grid.appendChild(thumb);
                };
                reader.readAsDataURL(file);
            }
        });
        container.appendChild(grid);
    }
    window.previewMultiSpotUpload = previewMultiSpotUpload;

    function switchSettingsTab(tabKey, cardEl, event, doScroll) {
        if (event) {
            if (typeof event.preventDefault === 'function') event.preventDefault();
            if (typeof event.stopPropagation === 'function') event.stopPropagation();
        }
        if (!tabKey) return;
        if (typeof doScroll === 'undefined') doScroll = true;
        
        // 1. Highlight active card in grid
        document.querySelectorAll('.adm-setting-card-btn').forEach(function(btn) {
            btn.classList.remove('is-active');
            var badge = btn.querySelector('.active-badge');
            if (badge) badge.style.display = 'none';
        });
        var activeBtn = cardEl || document.getElementById('card-' + tabKey) || document.querySelector(".adm-setting-card-btn[data-tab='" + tabKey + "']");
        if (activeBtn) {
            activeBtn.classList.add('is-active');
            var badge = activeBtn.querySelector('.active-badge');
            if (badge) badge.style.display = 'block';
        }

        // 2. Hide other panes and display target active pane directly below cards
        var targetPane = document.getElementById('pane-' + tabKey);
        if (targetPane) {
            document.querySelectorAll('.adm-settings-tab-pane').forEach(function(pane) {
                if (pane !== targetPane) {
                    pane.classList.remove('is-active');
                    pane.style.display = 'none';
                }
            });
            targetPane.classList.add('is-active');
            targetPane.style.display = 'block';

            // Smoothly bring the active editing form comfortably into view
            if (doScroll) {
                setTimeout(function() {
                    try {
                        targetPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch(err) {
                        var rect = targetPane.getBoundingClientRect();
                        var topPos = window.pageYOffset + rect.top - 85;
                        window.scrollTo({ top: Math.max(0, topPos), behavior: 'smooth' });
                    }
                }, 40);
            }

            if (tabKey === 'sanctuary_map' && typeof window.initAdminMasterMapStudio === 'function') {
                setTimeout(window.initAdminMasterMapStudio, 80);
            }
        }

        // 3. Keep hidden active_tab fields synchronized across forms
        document.querySelectorAll('input[name="active_tab"]').forEach(function(input) {
            input.value = tabKey;
        });

        // 4. Update banner indicator text if present
        var tabTitleMap = {
            'climate': 'CARD 01 • CLIMATE TICKER & SANCTUARY ACCOLADES',
            'hero': 'CARD 02 • HERO MARQUEE & VISUAL BACKDROP',
            'philosophy': 'CARD 03 • SANCTUARY PHILOSOPHY & WELCOME MANIFESTO',
            'rooms': 'CARD 04 • VILLAS & COTTAGES (DYNAMIC TARIFFS & SPECS)',
            'experiences': 'CARD 05 • CURATED EXPERIENCES & RITUALS (DYNAMIC CMS)',
            'menu': 'CARD 06 • FOOD MENU & LIVING GASTRONOMY HUB (DYNAMIC CMS)',
            'why': 'CARD 07 • WHY FOOD FOREST? (LIVING SOIL & COB ARCHITECTURE)',
            'sanctuary_map': 'CARD 08 • SANCTUARY ESTATE MAP & MOUNTAIN ROUTE TRAILS',
            'seasons': 'CARD 09 • SEASONS OF KANTHALLOOR (DYNAMIC CMS)',
            'gallery': 'CARD 10 • VISUAL DIARY (8 PHOTO CHRONICLE)',
            'testimonials': 'CARD 11 • GUEST REFLECTIONS (TESTIMONIALS & REVIEWS)',
            'whatsapp': 'CARD 12 • WHATSAPP CONCIERGE & COMMUNICATION CHANNELS',
            'estate': 'CARD 13 • ESTATE BRANDING & OPERATIONAL IDENTITY',
            'protection': 'CARD 14 • WEBSITE CONTENT & IMAGE SHIELD',
            'security': 'CARD 15 • ADMINISTRATOR SECURITY & ACCESS KEY',
            'backup': 'CARD 16 • MYSQL DATABASE BACKUP & RESTORE'
        };
        var activeLabel = document.getElementById('active-tab-label');
        if (activeLabel && tabTitleMap[tabKey]) {
            activeLabel.textContent = tabTitleMap[tabKey];
        }

        // 5. Update Quick Jump Dropdown if present
        var jumpSelect = document.getElementById('adm-section-jump-select');
        if (jumpSelect) {
            jumpSelect.value = tabKey;
        }

        // 6. Update Live Website Anchor Link
        var anchorMap = {
            'estate': '../index.php',
            'whatsapp': '../index.php#whatsapp',
            'hero': '../index.php#hero',
            'climate': '../index.php#climate',
            'philosophy': '../index.php#welcome',
            'why': '../index.php#why-mudhouse',
            'experiences': '../index.php#experiences',
            'menu': '../index.php#dining',
            'seasons': '../index.php#seasons',
            'sanctuary_map': '../index.php#sanctuary-map',
            'rooms': '../index.php#villas',
            'gallery': '../index.php#gallery',
            'testimonials': '../index.php#reviews',
            'protection': '../index.php',
            'security': 'settings.php?tab=security',
            'backup': 'settings.php?tab=backup'
        };
        var liveAnchorLink = document.getElementById('adm-btn-live-anchor');
        if (liveAnchorLink && anchorMap[tabKey]) {
            liveAnchorLink.href = anchorMap[tabKey];
        }

        // 7. Update Live Preview Frame iframe source
        var pvIframe = document.getElementById('adm-live-preview-iframe');
        if (pvIframe) {
            pvIframe.src = 'preview_frame.php?section=' + tabKey;
        }

        // 8. Update browser URL parameter without reloading
        if (window.history && window.history.replaceState) {
            var url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    }
    window.switchSettingsTab = switchSettingsTab;