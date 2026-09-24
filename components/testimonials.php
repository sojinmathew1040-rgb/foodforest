<!-- Guest Stories (Testimonials & Reviews) Section -->
<section id="testimonials" class="testimonials-section section-padding">
    <div class="container">
        
        <!-- Header with Navigation & Share Review Controls -->
        <div class="testimonials-header-bar scroll-reveal">
            <div class="testimonials-header-text">
                <span class="section-label"><?php echo htmlspecialchars(get_setting('testimonials_badge', 'Guest Stories & Chronicles')); ?></span>
                <h3 class="section-title font-serif split-text" style="color: var(--accent-green);"><?php echo htmlspecialchars(get_setting('testimonials_title', 'Moments Cherished, Memories Shared')); ?></h3>
                <p class="testimonials-desc font-sans" style="color: var(--text-light); max-width: 680px; margin-top: 10px;">
                    <?php echo htmlspecialchars(get_setting('testimonials_desc', 'Unfiltered impressions from travelers who have slept beneath our high-range canopies and lived in our handcrafted earthen mudhouses.')); ?>
                </p>
            </div>
            
            <div class="testimonials-header-actions" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                <!-- Share Story Button -->
                <button type="button" class="btn-share-reflection magnetic" data-strength="10" onclick="openGuestReviewModal();">
                    <i class="fa-solid fa-feather-pointed"></i>
                    <span>Share Your Story</span>
                </button>

                <!-- Slider Control Buttons -->
                <div class="testimonials-slider-controls">
                    <button type="button" class="btn-testimonial-nav prev magnetic" data-strength="10" id="btn-prev-testimonial" aria-label="Scroll Left">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button type="button" class="btn-testimonial-nav next magnetic" data-strength="10" id="btn-next-testimonial" aria-label="Scroll Right">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Scrollable Testimonials Carousel (Mouse drag, Touch swipe, Auto-scroll) -->
        <div class="testimonials-carousel-container" id="testimonials-carousel-container">
            <div class="testimonials-track" id="testimonials-track">
                <?php
                require_once __DIR__ . '/../admin/includes/db.php';
                $testimonials_list = get_testimonials();
                $all_properties = get_sanctuary_properties_list();
                if (!empty($testimonials_list)):
                    foreach ($testimonials_list as $t):
                        $stars_val = isset($t['stars']) ? (float)$t['stars'] : 5.0;
                        
                        // Resolve interactive link target for property badge
                        $badge_text = $t['stay_badge'] ?? 'Canopy Treehouse Villa';
                        $badge_link = '#rooms-experience';
                        if (stripos($badge_text, 'dining') !== false || stripos($badge_text, 'gastro') !== false || stripos($badge_text, 'food') !== false || stripos($badge_text, 'woodfire') !== false) {
                            $badge_link = '#dining';
                        } elseif (stripos($badge_text, 'orchard') !== false || stripos($badge_text, 'stargaz') !== false || stripos($badge_text, 'trail') !== false || stripos($badge_text, 'exp') !== false) {
                            $badge_link = '#experiences';
                        } elseif (stripos($badge_text, 'retreat') !== false || stripos($badge_text, 'day') !== false || stripos($badge_text, 'philosophy') !== false) {
                            $badge_link = '#philosophy';
                        } elseif (stripos($badge_text, 'tree') !== false || stripos($badge_text, 'mud') !== false || stripos($badge_text, 'villa') !== false || stripos($badge_text, 'cottage') !== false || stripos($badge_text, 'hut') !== false) {
                            $badge_link = '#rooms-experience';
                        }
                        $is_google = (!empty($t['source']) && $t['source'] === 'google_maps');
                ?>
                    <!-- Dynamic Testimonial Card -->
                    <div class="testimonial-card">
                        <div class="testimonial-card-top">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <?php echo render_star_rating_html($stars_val, 'testimonial-stars'); ?>
                            </div>
                            <?php if ($is_google): ?>
                                <!-- Google Review Badge in place of property name -->
                                <a href="https://maps.app.goo.gl/WrLRy4j8aSU7xtM4A" target="_blank" rel="noopener noreferrer" class="stay-badge-interactive google-stay-badge font-sans" title="Verified Google Maps Review · Food Forest Kanthalloor">
                                    <i class="fa-brands fa-google" style="color: #4285F4; font-size: 11px;"></i>
                                    <span>Google Review</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 8px; margin-left: 3px; opacity: 0.85;"></i>
                                </a>
                            <?php else: ?>
                                <!-- Interactive Property Badge Button for Website Submissions -->
                                <a href="<?php echo htmlspecialchars($badge_link); ?>" class="stay-badge-interactive font-sans" title="Explore <?php echo htmlspecialchars($badge_text); ?>">
                                    <span><?php echo htmlspecialchars($badge_text); ?></span>
                                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 8.5px; margin-left: 4px; opacity: 0.85;"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($t['title'])): ?>
                            <h4 class="testimonial-heading font-serif" style="color: #FAF8F5; font-size: 1.15rem; margin: 0 0 8px;">
                                <?php echo htmlspecialchars($t['title']); ?>
                            </h4>
                        <?php endif; ?>

                        <p class="testimonial-quote font-serif">
                            "<?php echo htmlspecialchars($t['quote']); ?>"
                        </p>

                        <!-- Attached Media Preview (Photo or Video Vlog) -->
                        <?php if (!empty($t['media_url'])): ?>
                            <div class="testimonial-media-box">
                                <?php if (($t['media_type'] ?? '') === 'video'): ?>
                                    <div class="testimonial-video-pill" onclick="openReviewMedia('video', '<?php echo htmlspecialchars($t['media_url']); ?>', '<?php echo htmlspecialchars(addslashes($t['guest_name'])); ?>');">
                                        <i class="fa-solid fa-circle-play"></i>
                                        <span>Watch Guest Video Vlog</span>
                                    </div>
                                <?php else: ?>
                                    <div class="testimonial-photo-thumb" onclick="openReviewMedia('image', '<?php echo htmlspecialchars($t['media_url']); ?>', '<?php echo htmlspecialchars(addslashes($t['guest_name'])); ?>');">
                                        <img src="<?php echo htmlspecialchars($t['media_url']); ?>" alt="Guest Experience Photo" loading="lazy">
                                        <span class="photo-expand-tag font-sans"><i class="fa-solid fa-expand"></i> View Memory</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Official Estate Concierge Response / Reply -->
                        <?php if (!empty($t['admin_reply'])): ?>
                            <div class="testimonial-concierge-reply">
                                <div class="reply-header">
                                    <i class="fa-solid fa-seedling" style="color: var(--accent-gold, #C5A059);"></i>
                                    <span class="reply-title font-sans">Estate Concierge Reply</span>
                                    <?php if (!empty($t['admin_reply_at'])): ?>
                                        <span class="reply-date font-sans"><?php echo date('M d, Y', strtotime($t['admin_reply_at'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="reply-content font-sans">
                                    <?php echo htmlspecialchars($t['admin_reply']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="testimonial-author-row">
                            <?php if (!empty($t['avatar_url'])): ?>
                                <img src="<?php echo htmlspecialchars($t['avatar_url']); ?>" alt="<?php echo htmlspecialchars($t['guest_name']); ?>" class="author-avatar-img">
                            <?php else: ?>
                                <div class="author-avatar font-serif"><?php echo htmlspecialchars($t['initials'] ?: 'FF'); ?></div>
                            <?php endif; ?>
                            <div class="author-info">
                                <span class="author-name font-sans"><?php echo htmlspecialchars($t['guest_name']); ?></span>
                                <span class="author-location font-sans"><?php echo htmlspecialchars($t['guest_location']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>

    </div>

    <!-- Luxury Guest Reflection & Review Submission Modal -->
    <div id="guest-review-modal" class="guest-review-modal" role="dialog" aria-modal="true" style="display: none;">
        <div class="guest-review-backdrop" onclick="closeGuestReviewModal();"></div>
        <div class="guest-review-dialog">
            <div class="guest-review-header">
                <div class="guest-review-title-group">
                    <span class="guest-review-eyebrow font-sans">SANCTUARY MEMOIR</span>
                    <h3 class="font-serif guest-review-heading">Share Your Sanctuary Reflection</h3>
                    <p class="font-sans guest-review-sub">Your authentic impressions help conscious travelers discover the tranquility of Food Forest Kanthalloor.</p>
                </div>
                <button type="button" class="guest-review-close-btn" onclick="closeGuestReviewModal();" aria-label="Close Modal">✕</button>
            </div>

            <form id="guest-review-form" enctype="multipart/form-data" onsubmit="handleGuestReviewSubmit(event);">
                <div class="guest-review-body">
                    
                    <!-- Interactive Star Rating Picker (Full & Half Stars Support) -->
                    <div class="review-form-group star-picker-group">
                        <label class="review-form-label font-sans">Your Experience Rating *</label>
                        <div class="interactive-star-rating-box">
                            <div class="interactive-stars" id="interactive-stars-picker">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <div class="star-unit" data-star-num="<?php echo $s; ?>">
                                        <div class="star-half left" data-star-val="<?php echo $s - 0.5; ?>" title="<?php echo $s - 0.5; ?> Stars"></div>
                                        <div class="star-half right" data-star-val="<?php echo $s; ?>" title="<?php echo $s; ?> Stars"></div>
                                        <i class="fa-solid fa-star star-bg"></i>
                                        <i class="fa-solid fa-star star-fill"></i>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="stars" id="review-stars-input" value="5.0">
                            <span class="star-rating-label font-sans" id="star-rating-text">5.0 / 5.0 (Exceptional Sanctuary Experience)</span>
                        </div>
                    </div>

                    <!-- Personal Information Grid -->
                    <div class="review-form-row">
                        <div class="review-form-group">
                            <label class="review-form-label font-sans">Your Full Name *</label>
                            <input type="text" name="guest_name" class="review-form-control font-sans" placeholder="e.g. Maya & Arjun Raman" required>
                        </div>
                        <div class="review-form-group">
                            <label class="review-form-label font-sans">Your City / Country *</label>
                            <input type="text" name="guest_location" class="review-form-control font-sans" placeholder="e.g. Bangalore, India" required>
                        </div>
                    </div>

                    <div class="review-form-row">
                        <div class="review-form-group">
                            <label class="review-form-label font-sans">Dwelling / Experience Stayed *</label>
                            <select name="stay_badge" class="review-form-control font-sans" style="cursor: pointer;" required>
                                <?php foreach ($all_properties as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p['title']); ?>">
                                        <?php echo htmlspecialchars($p['title']); ?> (<?php echo ucfirst($p['type']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="review-form-group">
                            <label class="review-form-label font-sans">Review Title (Optional)</label>
                            <input type="text" name="title" class="review-form-control font-sans" placeholder="e.g. Unforgettable high-canopy serenity">
                        </div>
                    </div>

                    <!-- Narrative Reflection Text -->
                    <div class="review-form-group">
                        <label class="review-form-label font-sans">Your Reflection & Impressions *</label>
                        <textarea name="quote" rows="4" class="review-form-control font-sans" placeholder="Describe the sounds of the mountain stream, morning mist, fireplace warmth, or earthen flavors..." required></textarea>
                    </div>

                    <!-- Media Uploads: Avatar & Photo/Video Vlog -->
                    <div class="review-form-row media-upload-row">
                        <div class="review-form-group">
                            <label class="review-form-label font-sans">
                                <i class="fa-solid fa-user-circle"></i> Guest Photo / Avatar (Optional)
                            </label>
                            <div class="custom-file-upload">
                                <input type="file" name="avatar_file" id="review-avatar-input" accept="image/*" onchange="previewReviewAvatar(this);">
                                <label for="review-avatar-input" class="file-upload-trigger font-sans">
                                    <span id="review-avatar-trigger-text"><i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Avatar</span>
                                </label>
                                <div id="review-avatar-preview" class="avatar-preview-box" style="display: none;"></div>
                            </div>
                        </div>

                        <div class="review-form-group">
                            <label class="review-form-label font-sans">
                                <i class="fa-solid fa-photo-film"></i> Attach Photo or Video Vlog (Optional)
                            </label>
                            <div class="custom-file-upload">
                                <input type="file" name="media_file" id="review-media-input" accept="image/*,video/*" onchange="previewReviewMedia(this);">
                                <label for="review-media-input" class="file-upload-trigger font-sans">
                                    <span id="review-media-trigger-text"><i class="fa-solid fa-camera"></i> Upload Photo / Video</span>
                                </label>
                                <div id="review-media-preview-box" class="media-preview-box" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="review-form-group" style="margin-top: 6px;">
                        <label class="review-form-label font-sans" style="font-size: 0.78rem; opacity: 0.85;">Or Video Vlog URL (YouTube / Vimeo / MP4)</label>
                        <input type="url" name="media_url" class="review-form-control font-sans" placeholder="https://youtube.com/watch?v=... or https://...">
                    </div>

                </div>

                <div class="guest-review-footer">
                    <button type="button" class="btn-review-cancel font-sans" onclick="closeGuestReviewModal();">Cancel</button>
                    <button type="submit" id="btn-submit-review" class="btn-review-submit font-sans">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Submit Sanctuary Reflection</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Lightbox Modal for Review Photos & Video Vlogs -->
    <div id="review-media-modal" class="review-media-modal" role="dialog" aria-modal="true" style="display: none;">
        <div class="review-media-backdrop" onclick="closeReviewMedia();"></div>
        <div class="review-media-container">
            <button type="button" class="review-media-close" onclick="closeReviewMedia();" aria-label="Close">✕</button>
            <div id="review-media-body" class="review-media-body"></div>
            <div id="review-media-caption" class="review-media-caption font-sans"></div>
        </div>
    </div>

</section>

<!-- Review Submission & Interactive Star Rating JavaScript -->
<script>
(function() {
    // Star Rating Descriptions
    var ratingLabels = {
        0.5: '0.5 / 5.0 (Initial Impression)',
        1.0: '1.0 / 5.0 (Needs Improvement)',
        1.5: '1.5 / 5.0 (Below Expectations)',
        2.0: '2.0 / 5.0 (Fair)',
        2.5: '2.5 / 5.0 (Average Sanctuary Stay)',
        3.0: '3.0 / 5.0 (Good)',
        3.5: '3.5 / 5.0 (Very Good Mountain Escape)',
        4.0: '4.0 / 5.0 (Memorable Experience)',
        4.5: '4.5 / 5.0 (Outstanding Tranquility & Hospitality)',
        5.0: '5.0 / 5.0 (Exceptional Sanctuary Experience)'
    };

    var currentRating = 5.0;

    function updateStarsVisual(rating) {
        var picker = document.getElementById('interactive-stars-picker');
        if (!picker) return;
        var starUnits = picker.querySelectorAll('.star-unit');
        starUnits.forEach(function(unit) {
            var sNum = parseInt(unit.getAttribute('data-star-num'), 10);
            var fill = unit.querySelector('.star-fill');
            if (rating >= sNum) {
                fill.style.clipPath = 'inset(0 0 0 0)';
                fill.style.opacity = '1';
            } else if (rating >= (sNum - 0.5)) {
                fill.style.clipPath = 'inset(0 50% 0 0)';
                fill.style.opacity = '1';
            } else {
                fill.style.clipPath = 'inset(0 100% 0 0)';
                fill.style.opacity = '0';
            }
        });
        var textEl = document.getElementById('star-rating-text');
        var inputEl = document.getElementById('review-stars-input');
        if (textEl) textEl.innerText = ratingLabels[rating] || (rating.toFixed(1) + ' / 5.0');
        if (inputEl) inputEl.value = rating.toFixed(1);
    }

    function initStarPicker() {
        var picker = document.getElementById('interactive-stars-picker');
        if (!picker) return;
        var halves = picker.querySelectorAll('.star-half');
        halves.forEach(function(half) {
            half.addEventListener('mouseenter', function() {
                var val = parseFloat(half.getAttribute('data-star-val'));
                updateStarsVisual(val);
            });
            half.addEventListener('click', function() {
                currentRating = parseFloat(half.getAttribute('data-star-val'));
                updateStarsVisual(currentRating);
            });
        });
        picker.addEventListener('mouseleave', function() {
            updateStarsVisual(currentRating);
        });
        updateStarsVisual(currentRating);
    }

    window.openGuestReviewModal = function() {
        var modal = document.getElementById('guest-review-modal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(function() { modal.classList.add('active'); }, 10);
            document.body.style.overflow = 'hidden';
            initStarPicker();
        }
    };

    window.closeGuestReviewModal = function() {
        var modal = document.getElementById('guest-review-modal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(function() { modal.style.display = 'none'; }, 250);
            document.body.style.overflow = '';
        }
    };

    window.previewReviewAvatar = function(input) {
        var prev = document.getElementById('review-avatar-preview');
        var label = document.getElementById('review-avatar-trigger-text');
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                if (prev) {
                    prev.style.display = 'block';
                    prev.innerHTML = '<img src="' + e.target.result + '" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid #c5a059;">';
                }
                if (label) label.innerHTML = '✓ ' + input.files[0].name.substring(0, 16) + '...';
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    window.previewReviewMedia = function(input) {
        var prev = document.getElementById('review-media-preview-box');
        var label = document.getElementById('review-media-trigger-text');
        if (input.files && input.files[0]) {
            var file = input.files[0];
            var isVid = file.type.startsWith('video');
            if (prev) {
                prev.style.display = 'inline-block';
                prev.innerHTML = '<span class="adm-badge" style="background: rgba(197,160,89,0.2); color: #c5a059; padding: 4px 8px; border-radius: 4px; font-size: 11px;">' + (isVid ? '🎬 Video: ' : '📷 Photo: ') + file.name.substring(0, 20) + '</span>';
            }
            if (label) label.innerHTML = '✓ Attached';
        }
    };

    window.handleGuestReviewSubmit = function(e) {
        e.preventDefault();
        var form = document.getElementById('guest-review-form');
        var submitBtn = document.getElementById('btn-submit-review');
        if (!form || !submitBtn) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Submitting...</span>';

        var formData = new FormData(form);

        fetch('api/submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> <span>Submit Sanctuary Reflection</span>';
            if (data.success) {
                form.reset();
                closeGuestReviewModal();
                showReviewToast(data.message || 'Thank you! Your reflection has been submitted.');
            } else {
                alert(data.message || 'Could not submit review. Please try again.');
            }
        })
        .catch(function(err) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> <span>Submit Sanctuary Reflection</span>';
            alert('An error occurred. Please try again.');
        });
    };

    function showReviewToast(msg) {
        var toast = document.createElement('div');
        toast.className = 'sanctuary-toast-notify font-sans';
        toast.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #2ecc71; font-size: 16px;"></i> <span>' + msg + '</span>';
        document.body.appendChild(toast);
        setTimeout(function() { toast.classList.add('visible'); }, 50);
        setTimeout(function() {
            toast.classList.remove('visible');
            setTimeout(function() { toast.remove(); }, 400);
        }, 5000);
    }

    // Media Popup (Photo / Video vlog)
    window.openReviewMedia = function(type, url, guestName) {
        var modal = document.getElementById('review-media-modal');
        var body = document.getElementById('review-media-body');
        var cap = document.getElementById('review-media-caption');
        if (!modal || !body) return;

        if (type === 'video') {
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                var vidId = url.split('v=')[1] || url.split('/').pop();
                body.innerHTML = '<iframe src="https://www.youtube.com/embed/' + vidId + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%; height:450px; border-radius:12px;"></iframe>';
            } else {
                body.innerHTML = '<video src="' + url + '" controls autoplay style="max-width:100%; max-height:75vh; border-radius:12px;"></video>';
            }
        } else {
            body.innerHTML = '<img src="' + url + '" alt="Guest Reflection" style="max-width:100%; max-height:80vh; object-fit:contain; border-radius:12px;">';
        }

        if (cap) cap.innerText = guestName ? ('Memory shared by ' + guestName) : 'Sanctuary Experience Memory';

        modal.style.display = 'flex';
        setTimeout(function() { modal.classList.add('active'); }, 10);
        document.body.style.overflow = 'hidden';
    };

    window.closeReviewMedia = function() {
        var modal = document.getElementById('review-media-modal');
        var body = document.getElementById('review-media-body');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(function() { 
                modal.style.display = 'none';
                if (body) body.innerHTML = '';
            }, 250);
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        initStarPicker();
    });
})();
</script>

<style>
.stay-badge-interactive {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: rgba(197, 160, 89, 0.12);
    color: var(--accent-gold, #C5A059);
    border: 1px solid rgba(197, 160, 89, 0.28);
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.stay-badge-interactive:hover {
    background: rgba(197, 160, 89, 0.25);
    border-color: var(--accent-gold, #C5A059);
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(197, 160, 89, 0.18);
}
.google-stay-badge {
    background: rgba(66, 133, 244, 0.12) !important;
    color: #70a6ff !important;
    border: 1px solid rgba(66, 133, 244, 0.32) !important;
}
.google-stay-badge:hover {
    background: rgba(66, 133, 244, 0.24) !important;
    border-color: #4285F4 !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 14px rgba(66, 133, 244, 0.25) !important;
}
.google-review-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    background: rgba(66, 133, 244, 0.1);
    color: #8ab4f8;
    border: 1px solid rgba(66, 133, 244, 0.25);
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}
.testimonial-concierge-reply {
    margin-top: 14px;
    padding: 12px 14px;
    background: rgba(197, 160, 89, 0.06);
    border-left: 2px solid var(--accent-gold, #C5A059);
    border-radius: 0 8px 8px 0;
}
.testimonial-concierge-reply .reply-header {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 5px;
}
.testimonial-concierge-reply .reply-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--accent-gold, #C5A059);
}
.testimonial-concierge-reply .reply-date {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.45);
    margin-left: auto;
}
.testimonial-concierge-reply .reply-content {
    font-size: 0.85rem;
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.88);
    margin: 0;
}
</style>
