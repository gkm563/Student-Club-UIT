/**
 * USC UIT — Redesigned Interactive Institutional Governance Matrix & Tree
 * Light Executive Theme with Level Indicators, Perfect Spacing & 100% Mobile Responsiveness.
 */

class UscOrgTree {
    static init(containerId, options = {}) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const defaultData = {
            root: {
                id: 'apex-council',
                name: 'USC UIT Apex Governing Council',
                subtitle: 'United Student Council — Apex Governing Body',
                badge: 'LEVEL 01 • APEX GOVERNANCE',
                session: 'Session 2024–2025',
                icon: 'bi-shield-lock-fill',
                accentColor: '#d97706',
                url: 'index.html',
                faculty: 'Er. Gaurav Narain & Dr. Ankit Gupta',
                dean: 'Dean Student Welfare',
                president: 'Student Executive Council',
                desc: 'The apex institutional body governing all Technical, Cultural, and Academic sub-chapters across United Institute of Technology (UIT).'
            },
            wings: [
                {
                    id: 'tech-wing',
                    slug: 'technical',
                    levelTag: 'LEVEL 02 • TECHNICAL WING',
                    name: 'Developers Club UIT',
                    tagline: 'Technical Wing',
                    faculty: 'Er. Gaurav Narain & Er. Kushagra Dwivedi',
                    leads: 'Student Technical Coordinators',
                    members: '500+ Active Members',
                    icon: 'bi-code-slash',
                    accentColor: '#2563eb',
                    glowColor: '#eff6ff',
                    badgeTextColor: '#1d4ed8',
                    url: 'developers-club.html',
                    desc: 'Drives technical excellence, open-source initiatives, hackathons, cloud computing, and algorithmic coding culture.',
                    chapters: [
                        { 
                            id: 'gfg',
                            name: 'GFG SC UIT',
                            category: 'GeeksforGeeks Chapter',
                            icon: 'bi-lightning-charge-fill',
                            url: 'clubs.html?search=GeeksforGeeks',
                            badge: '120+ Coders',
                            faculty: 'Er. Gaurav Narain',
                            leads: 'Chapter Leads & Mentors',
                            schedule: 'Wednesdays @ 4:00 PM',
                            location: 'Lab 3, UIT Tech Block',
                            desc: 'Official GeeksforGeeks student chapter focusing on Data Structures, Algorithms, Coding Contests, and Interview Prep.'
                        },
                        { 
                            id: 'gdg',
                            name: 'GDG Cloud UIT',
                            category: 'Google Developer Group',
                            icon: 'bi-cloud-fill',
                            url: 'clubs.html?search=GDG',
                            badge: '150+ Members',
                            faculty: 'Er. Kushagra Dwivedi',
                            leads: 'Cloud Leads & Evangelists',
                            schedule: 'Fridays @ 3:30 PM',
                            location: 'Seminar Hall B',
                            desc: 'Official Google Developer Student Community for GCP, DevOps, Android, Web Development, and GenAI.'
                        },
                        { 
                            id: 'gemini',
                            name: 'Gemini Builders',
                            category: 'AI & Hackathon Guild',
                            icon: 'bi-stars',
                            url: 'clubs.html?search=Gemini',
                            badge: '80+ Innovators',
                            faculty: 'Er. Gaurav Narain',
                            leads: 'AI Hackathon Mentors',
                            schedule: 'Saturdays @ 2:00 PM',
                            location: 'Innovation Lab',
                            desc: 'Specialized guild for Generative AI builders, LLM application engineering, and national hackathon teams.'
                        },
                        { 
                            id: 'dsa',
                            name: 'DSA & Competitive',
                            category: 'Coding & Contests',
                            icon: 'bi-cpu-fill',
                            url: 'clubs.html?category=technical',
                            badge: '100+ Coders',
                            faculty: 'Er. Kushagra Dwivedi',
                            leads: 'Competitive Coding Captains',
                            schedule: 'Tuesdays @ 5:00 PM',
                            location: 'Coding Lab 1',
                            desc: 'Intensive problem-solving sessions on LeetCode, Codeforces, and ICPC algorithmic contest preparation.'
                        },
                        { 
                            id: 'cyber',
                            name: 'Cyber & Cloud',
                            category: 'Open Source & Security',
                            icon: 'bi-terminal-fill',
                            url: 'clubs.html?category=technical',
                            badge: '60+ Devs',
                            faculty: 'Er. Gaurav Narain',
                            leads: 'CTF & Linux Leads',
                            schedule: 'Thursdays @ 4:30 PM',
                            location: 'Cyber Sec Wing',
                            desc: 'Ethical hacking, Capture-The-Flag (CTF) challenges, Linux system administration, and open-source contributions.'
                        }
                    ]
                },
                {
                    id: 'cultural-wing',
                    slug: 'cultural',
                    levelTag: 'LEVEL 02 • CULTURAL WING',
                    name: 'Cultural Club UIT',
                    tagline: 'Cultural Wing',
                    faculty: 'Dr. Ankit Gupta',
                    leads: 'Student Cultural Secretaries',
                    members: '350+ Active Members',
                    icon: 'bi-palette-fill',
                    accentColor: '#e11d48',
                    glowColor: '#fff1f2',
                    badgeTextColor: '#be123c',
                    url: 'cultural-club.html',
                    desc: 'Celebrates artistic expression, music, stage performance, literature, and public speaking across campus and inter-college fests.',
                    chapters: [
                        { 
                            id: 'toastmasters',
                            name: 'Toastmasters UGI',
                            category: 'Oratory & Public Speaking',
                            icon: 'bi-mic-fill',
                            url: 'clubs.html?search=Toastmasters',
                            badge: '75+ Speakers',
                            faculty: 'Dr. Ankit Gupta',
                            leads: 'Toastmasters VP & Speakers',
                            schedule: 'Mondays @ 4:00 PM',
                            location: 'Main Auditorium',
                            desc: 'Public speaking, impromptu speeches, leadership development, and international Toastmasters certifications.'
                        },
                        { 
                            id: 'music',
                            name: 'Music & Band',
                            category: 'Vocal & Instruments',
                            icon: 'bi-music-note-beamed',
                            url: 'clubs.html?category=cultural',
                            badge: '90+ Musicians',
                            faculty: 'Dr. Ankit Gupta',
                            leads: 'Band Captains & Vocalists',
                            schedule: 'Wednesdays @ 5:00 PM',
                            location: 'Music Studio',
                            desc: 'Vocal melodies, instrumentals, acoustic jams, college rock bands, and grand fest stage performances.'
                        },
                        { 
                            id: 'dramatics',
                            name: 'Dramatics Society',
                            category: 'Theatre & Stage Arts',
                            icon: 'bi-masks',
                            url: 'clubs.html?category=cultural',
                            badge: '50+ Actors',
                            faculty: 'Dr. Ankit Gupta',
                            leads: 'Stage Directors & Writers',
                            schedule: 'Thursdays @ 4:00 PM',
                            location: 'Amphitheatre',
                            desc: 'Street plays (Nukkad Natak), stage plays, mime, scriptwriting, and national level theatre competitions.'
                        },
                        { 
                            id: 'finearts',
                            name: 'Fine Arts & Media',
                            category: 'Creative & Photography',
                            icon: 'bi-camera-fill',
                            url: 'clubs.html?category=creative',
                            badge: '65+ Artists',
                            faculty: 'Dr. Ankit Gupta',
                            leads: 'Creative Leads & Designers',
                            schedule: 'Fridays @ 3:00 PM',
                            location: 'Arts Studio',
                            desc: 'Digital illustration, canvas painting, photojournalism, short filmmaking, and fest decor design.'
                        },
                        { 
                            id: 'literary',
                            name: 'Literary Society',
                            category: 'Debate & Writing',
                            icon: 'bi-book-half',
                            url: 'clubs.html?category=academic',
                            badge: '40+ Writers',
                            faculty: 'Dr. Ankit Gupta',
                            leads: 'Editorial Board & Debaters',
                            schedule: 'Tuesdays @ 4:00 PM',
                            location: 'Central Library Hall',
                            desc: 'Parliamentary debates, poetry slams, creative writing contests, and annual college magazine editorial.'
                        }
                    ]
                }
            ]
        };

        const config = { ...defaultData, ...options };
        const instance = new UscOrgTree(container, config);
        instance.render();
        return instance;
    }

    constructor(container, config) {
        this.container = container;
        this.config = config;
        this.activeFilter = 'all';
        this.searchQuery = '';
    }

    render() {
        this.container.innerHTML = this.buildHtml();
        this.bindEvents();
    }

    buildHtml() {
        const { root, wings } = this.config;
        const totalChapters = wings.reduce((acc, w) => acc + w.chapters.length, 0);

        return `
            <div class="usc-org-tree-wrapper py-2 py-md-4" style="width: 100%; position: relative; clear: both;">
                
                <!-- Main Light Executive Container Card -->
                <div class="org-tree-card-bg" style="background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%) !important; border: 1.5px solid #e2e8f0 !important; border-radius: 24px !important; padding: 28px 18px !important; box-shadow: 0 15px 35px rgba(15, 23, 42, 0.06) !important; color: #0f172a !important; position: relative; overflow: hidden;">
                    
                    <!-- Header Section -->
                    <div class="org-tree-header text-center position-relative mb-4" style="z-index: 5;">
                        <div class="d-inline-flex align-items-center gap-2 mb-2 flex-wrap justify-content-center">
                            <span class="org-tree-badge" style="background: #fffbeb !important; color: #b45309 !important; border: 1.5px solid #fde68a !important; padding: 6px 16px !important; border-radius: 50px !important; font-weight: 800 !important; font-size: 0.75rem !important; letter-spacing: 0.5px !important; text-transform: uppercase;">
                                <i class="bi bi-diagram-3-fill me-1 text-warning"></i> INSTITUTIONAL GOVERNANCE MATRIX
                            </span>
                            <span class="org-tree-badge-pulse" style="background: #ecfdf5 !important; color: #047857 !important; border: 1px solid #a7f3d0 !important; padding: 5px 14px !important; border-radius: 50px !important; font-weight: 700 !important; font-size: 0.72rem !important;">
                                <span class="pulse-dot-green"></span> Interactive Live Flow
                            </span>
                        </div>
                        
                        <h3 class="org-tree-title" style="color: #0f172a !important; font-size: 1.85rem !important; font-weight: 900 !important; letter-spacing: -0.5px; margin-top: 10px; margin-bottom: 8px;">
                            USC UIT Hierarchy &amp; <span class="org-tree-title-gradient" style="background: linear-gradient(120deg, #2563eb 0%, #7c3aed 50%, #db2777 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 900;">Wing Structure</span>
                        </h3>
                        
                        <p class="org-tree-lead mx-auto mb-3" style="color: #475569 !important; max-width: 600px; font-size: 0.9rem !important; font-weight: 500; line-height: 1.5;">
                            Click any node to inspect governance profile • Search or filter by wing
                        </p>

                        <!-- Quick Metrics Bar -->
                        <div class="org-tree-metrics-bar d-flex flex-wrap align-items-center justify-content-center gap-2 mb-4">
                            <div class="metric-pill" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 50px !important; padding: 6px 14px !important; color: #334155 !important; font-size: 0.78rem !important; font-weight: 600; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <i class="bi bi-diagram-2 text-primary me-1"></i> <strong>2</strong> Wings
                            </div>
                            <div class="metric-pill" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 50px !important; padding: 6px 14px !important; color: #334155 !important; font-size: 0.78rem !important; font-weight: 600; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <i class="bi bi-grid-3x3-gap-fill text-info me-1"></i> <strong>${totalChapters}</strong> Chapters
                            </div>
                            <div class="metric-pill" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 50px !important; padding: 6px 14px !important; color: #334155 !important; font-size: 0.78rem !important; font-weight: 600; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <i class="bi bi-people-fill text-success me-1"></i> <strong>850+</strong> Members
                            </div>
                            <div class="metric-pill" style="background: #ffffff !important; border: 1px solid #e2e8f0 !important; border-radius: 50px !important; padding: 6px 14px !important; color: #334155 !important; font-size: 0.78rem !important; font-weight: 600; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                <i class="bi bi-person-workspace text-warning me-1"></i> <strong>3</strong> Advisors
                            </div>
                        </div>

                        <!-- Controls Bar: Live Search & View Filter Tabs -->
                        <div class="org-tree-controls-bar d-flex flex-column flex-md-row align-items-center justify-content-center gap-3 my-2 px-md-3">
                            
                            <!-- Search Bar -->
                            <div class="org-search-box position-relative w-100" style="max-width: 480px;">
                                <i class="bi bi-search search-icon" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 1rem; z-index: 10;"></i>
                                <input type="text" class="form-control org-search-input" id="orgTreeSearchInput" placeholder="Search chapters, leads, or keywords..." value="${this.searchQuery}" style="background: #ffffff !important; color: #0f172a !important; border: 1.5px solid #cbd5e1 !important; border-radius: 50px !important; padding: 10px 40px 10px 44px !important; font-size: 0.85rem !important; box-shadow: 0 2px 10px rgba(0,0,0,0.03) !important;">
                                <button type="button" class="btn btn-sm text-secondary btn-clear-search ${this.searchQuery ? '' : 'd-none'}" id="orgTreeSearchClear" title="Clear Search" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none;">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>

                            <!-- Filter Tabs -->
                            <div class="org-filter-tabs flex-wrap justify-content-center" style="background: #f1f5f9 !important; border: 1.5px solid #e2e8f0 !important; padding: 4px !important; border-radius: 50px !important; display: inline-flex; gap: 2px;">
                                <button type="button" class="btn org-filter-btn ${this.activeFilter === 'all' ? 'active' : ''}" data-filter="all" style="${this.activeFilter === 'all' ? 'background: #2563eb !important; color: #ffffff !important; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25) !important;' : 'background: transparent !important; color: #475569 !important;'} border-radius: 50px !important; padding: 6px 14px !important; font-weight: 700 !important; border: none !important; font-size: 0.78rem !important;">
                                    All Wings
                                </button>
                                <button type="button" class="btn org-filter-btn ${this.activeFilter === 'technical' ? 'active' : ''}" data-filter="technical" style="${this.activeFilter === 'technical' ? 'background: #2563eb !important; color: #ffffff !important; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25) !important;' : 'background: transparent !important; color: #475569 !important;'} border-radius: 50px !important; padding: 6px 14px !important; font-weight: 700 !important; border: none !important; font-size: 0.78rem !important;">
                                    Technical
                                </button>
                                <button type="button" class="btn org-filter-btn ${this.activeFilter === 'cultural' ? 'active' : ''}" data-filter="cultural" style="${this.activeFilter === 'cultural' ? 'background: #e11d48 !important; color: #ffffff !important; box-shadow: 0 4px 10px rgba(225, 29, 72, 0.25) !important;' : 'background: transparent !important; color: #475569 !important;'} border-radius: 50px !important; padding: 6px 14px !important; font-weight: 700 !important; border: none !important; font-size: 0.78rem !important;">
                                    Cultural
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Level 01: Apex Governing Council Card -->
                    <div class="org-tree-root-container my-3 ${this.shouldShowWing('apex') ? '' : 'd-none-filter'}" style="display: flex; justify-content: center; z-index: 5; position: relative;">
                        <div class="org-node org-node-root shadow-sm d-flex flex-column flex-sm-row align-items-center text-center text-sm-start" data-node-type="root" data-node-id="${root.id}" style="background: #ffffff !important; border: 2px solid #f59e0b !important; border-radius: 20px !important; padding: 20px !important; color: #0f172a !important; box-shadow: 0 8px 25px rgba(245, 158, 11, 0.12) !important; width: 100%; max-width: 680px; gap: 16px; cursor: pointer;">
                            
                            <div class="org-node-icon org-node-icon-gold" style="background: linear-gradient(135deg, #f59e0b, #d97706) !important; width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 1.5rem; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3); flex-shrink: 0;">
                                <i class="bi ${root.icon}"></i>
                            </div>
                            
                            <div class="org-node-content flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-1">
                                    <span class="org-node-tag" style="color: #b45309 !important; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.6px;">
                                        <i class="bi bi-award-fill me-1"></i>${root.badge}
                                    </span>
                                    <span class="badge" style="background: #fffbeb !important; color: #b45309 !important; border: 1px solid #fde68a !important; border-radius: 50px; padding: 3px 10px; font-size: 0.7rem; font-weight: 700;">
                                        ${root.session}
                                    </span>
                                </div>
                                
                                <div class="org-node-title" style="color: #0f172a !important; font-size: 1.25rem; font-weight: 900; line-height: 1.3;">${root.name}</div>
                                <div class="org-node-desc" style="color: #64748b !important; font-size: 0.82rem; font-weight: 500;">${root.subtitle}</div>
                                
                                <div class="org-node-meta mt-2 d-flex flex-wrap align-items-center gap-1.5">
                                    <span class="meta-item" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #e2e8f0 !important; padding: 3px 10px !important; border-radius: 50px !important; font-size: 0.74rem !important;">
                                        <i class="bi bi-person-badge me-1 text-warning"></i> ${root.faculty}
                                    </span>
                                    <span class="meta-item" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #e2e8f0 !important; padding: 3px 10px !important; border-radius: 50px !important; font-size: 0.74rem !important;">
                                        <i class="bi bi-buildings me-1 text-info"></i> ${root.dean}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="org-node-action d-none d-sm-flex align-items-center">
                                <span class="badge rounded-pill text-amber border" style="background: #fffbeb; color: #b45309; border-color: #fde68a; font-size: 0.75rem; padding: 6px 12px;">
                                    Inspect <i class="bi bi-chevron-right ms-1"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Connector System (Stem & Branch Bar) -->
                    <div class="org-tree-connector-system d-none d-lg-block" style="position: relative; width: 100%; margin: 6px 0 20px 0;">
                        <!-- Vertical Stem Top -->
                        <div class="org-tree-stem-top" style="width: 3px; height: 22px; background: linear-gradient(180deg, #f59e0b 0%, #2563eb 100%); margin: 0 auto; border-radius: 2px;"></div>
                        <!-- Horizontal Crossbar -->
                        <div class="org-tree-crossbar" style="width: 50%; height: 3px; background: linear-gradient(90deg, #2563eb 0%, #0284c7 50%, #e11d48 100%); margin: 0 auto; position: relative; border-radius: 2px;">
                            <!-- Drop Left -->
                            <div class="org-tree-drop org-tree-drop-left" style="position: absolute; left: 0; top: 0; width: 3px; height: 20px; background: #2563eb;">
                                <i class="bi bi-chevron-down org-drop-arrow" style="color: #2563eb; position: absolute; bottom: -12px; font-size: 1rem; font-weight: 900;"></i>
                            </div>
                            <!-- Drop Right -->
                            <div class="org-tree-drop org-tree-drop-right" style="position: absolute; right: 0; top: 0; width: 3px; height: 20px; background: #e11d48;">
                                <i class="bi bi-chevron-down org-drop-arrow" style="color: #e11d48; position: absolute; bottom: -12px; font-size: 1rem; font-weight: 900;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="org-tree-stem-mobile d-lg-none" style="width: 3px; height: 20px; background: linear-gradient(180deg, #f59e0b 0%, #2563eb 100%); margin: 0 auto 16px auto; border-radius: 2px;"></div>

                    <!-- Level 02 & 03: Wing Branches Grid -->
                    <div class="row g-4 justify-content-center org-tree-wings-row">
                        ${wings.map(wing => {
                            const isWingVisible = this.shouldShowWing(wing.slug);
                            return `
                            <div class="col-lg-6 ${isWingVisible ? '' : 'd-none-filter'}">
                                <div class="org-wing-branch-card" id="branch-${wing.id}" data-wing-slug="${wing.slug}" style="background: #ffffff !important; border: 1.5px solid #e2e8f0 !important; border-radius: 20px !important; padding: 18px !important; height: 100% !important; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04) !important;">
                                    
                                    <!-- Level Tag -->
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge" style="background: #f1f5f9; color: #475569; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.5px;">
                                            ${wing.levelTag}
                                        </span>
                                        <span class="badge" style="background:${wing.glowColor}; color:${wing.badgeTextColor}; font-size: 0.72rem; font-weight: 700; border-radius: 50px; padding: 3px 10px;">
                                            ${wing.members}
                                        </span>
                                    </div>

                                    <!-- Wing Card Header Node -->
                                    <div class="org-node org-node-wing ${wing.slug === 'cultural' ? 'org-node-wing-cultural' : 'org-node-wing-tech'}"
                                         data-node-type="wing" 
                                         data-wing-id="${wing.id}"
                                         style="${wing.slug === 'cultural' ? 'background: linear-gradient(135deg, #be123c 0%, #e11d48 100%) !important; box-shadow: 0 6px 18px rgba(225, 29, 72, 0.2) !important;' : 'background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important; box-shadow: 0 6px 18px rgba(37, 99, 235, 0.2) !important;'} border-radius: 16px !important; padding: 16px !important; cursor: pointer; display: flex; align-items: center; gap: 14px;">
                                        
                                        <div class="org-node-icon" style="background:#ffffff; color:${wing.accentColor}; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                                            <i class="bi ${wing.icon}"></i>
                                        </div>
                                        
                                        <div class="org-node-content flex-grow-1">
                                            <div class="org-node-title text-white d-flex align-items-center justify-content-between" style="color: #ffffff !important; font-size: 1.2rem; font-weight: 900;">
                                                <span>${wing.name}</span>
                                                <i class="bi bi-chevron-right fs-6 text-white opacity-75"></i>
                                            </div>
                                            <div class="org-node-faculty mt-0.5" style="color: rgba(255,255,255,0.9) !important; font-size: 0.8rem; font-weight: 500;">
                                                <i class="bi bi-person-badge me-1"></i> ${wing.faculty}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Connector Stem to Sub-Chapters -->
                                    <div class="org-wing-stem-box" style="display: flex; flex-direction: column; align-items: center; margin: 10px 0;">
                                        <div class="org-wing-stem-line" style="background:${wing.accentColor}; width: 2px; height: 14px; opacity: 0.8;"></div>
                                        <i class="bi bi-chevron-down org-wing-arrow" style="color:${wing.accentColor}; font-size: 0.9rem; font-weight: 900; margin-top: -3px;"></i>
                                    </div>

                                    <!-- Sub-Chapters Grid Box -->
                                    <div class="org-subchapters-container" style="background: #f8fafc !important; border: 1.5px solid #e2e8f0 !important; border-radius: 16px !important; padding: 14px !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2.5">
                                            <div class="org-subchapters-header text-uppercase m-0" style="color: #64748b !important; font-size: 0.72rem; font-weight: 800; letter-spacing: 0.6px;">
                                                <i class="bi bi-grid-fill me-1" style="color:${wing.accentColor};"></i> 
                                                LEVEL 03 • SUB-CHAPTERS (${wing.chapters.length})
                                            </div>
                                        </div>

                                        <div class="org-subchapters-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                                            ${wing.chapters.map(chap => {
                                                const isMatching = this.matchesSearch(chap);
                                                return `
                                                <div class="org-node-chip ${isMatching ? 'chip-matched' : 'chip-dimmed'}" 
                                                     data-node-type="chapter"
                                                     data-wing-id="${wing.id}"
                                                     data-chapter-id="${chap.id}"
                                                     style="background: #ffffff !important; border: 1.5px solid #e2e8f0 !important; border-radius: 12px !important; padding: 10px 12px !important; display: flex; align-items: center; gap: 10px; cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
                                                    
                                                    <div class="org-chip-icon" style="color:${wing.accentColor}; font-size: 1.2rem; flex-shrink: 0;">
                                                        <i class="bi ${chap.icon}"></i>
                                                    </div>
                                                    
                                                    <div class="org-chip-info flex-grow-1 min-w-0">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <span class="org-chip-name text-truncate" style="color: #0f172a !important; font-weight: 700; font-size: 0.82rem;">${chap.name}</span>
                                                            <i class="bi bi-info-circle-fill chip-info-icon" style="color: #cbd5e1; font-size: 0.85rem;" title="View Details"></i>
                                                        </div>
                                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                                            <span class="org-chip-category text-truncate" style="color: #64748b !important; font-size: 0.68rem;">${chap.category}</span>
                                                            <span class="org-chip-badge" style="background:${wing.glowColor}; color:${wing.badgeTextColor} !important; font-size: 0.64rem; font-weight: 800; padding: 2px 6px; border-radius: 8px; white-space: nowrap;">${chap.badge}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                                            }).join('')}
                                        </div>
                                    </div>

                                    <!-- Wing Footer Link -->
                                    <div class="mt-3 text-end">
                                        <a href="${wing.url}" class="wing-explore-link" style="color: #2563eb !important; font-size: 0.8rem; font-weight: 700; text-decoration: none;">
                                            Explore Full ${wing.name} Page <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                        }).join('')}
                    </div>
                </div>

                <!-- Interactive Detail Drawer / Modal Backdrop -->
                <div class="org-modal-backdrop d-none" id="orgTreeModalBackdrop" style="position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 100050; display: flex; align-items: center; justify-content: center; padding: 16px;">
                    <div class="org-modal-card" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 24px; width: 100%; max-width: 580px; padding: 28px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18); position: relative; color: #0f172a;">
                        <button type="button" class="btn-close org-modal-close" id="orgTreeModalClose" style="position: absolute; top: 18px; right: 18px; opacity: 0.8;"></button>
                        <div id="orgTreeModalContent">
                            <!-- Dynamic Content Injected Here -->
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    shouldShowWing(wingSlug) {
        if (this.activeFilter === 'all') return true;
        if (this.activeFilter === 'apex') return wingSlug === 'apex';
        return this.activeFilter === wingSlug;
    }

    matchesSearch(chapter) {
        if (!this.searchQuery || this.searchQuery.trim() === '') return true;
        const q = this.searchQuery.toLowerCase().trim();
        return (
            chapter.name.toLowerCase().includes(q) ||
            chapter.category.toLowerCase().includes(q) ||
            chapter.desc.toLowerCase().includes(q) ||
            (chapter.badge && chapter.badge.toLowerCase().includes(q))
        );
    }

    bindEvents() {
        const rootNode = this.container.querySelector('.org-node-root');
        const searchInput = this.container.querySelector('#orgTreeSearchInput');
        const clearBtn = this.container.querySelector('#orgTreeSearchClear');
        const filterBtns = this.container.querySelectorAll('.org-filter-btn');
        const nodeChips = this.container.querySelectorAll('.org-node-chip');
        const wingCards = this.container.querySelectorAll('.org-node-wing');
        const modalBackdrop = this.container.querySelector('#orgTreeModalBackdrop');
        const modalClose = this.container.querySelector('#orgTreeModalClose');

        // Filter tab click
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter');
                this.activeFilter = filter;
                this.render();
            });
        });

        // Search input
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value;
                if (clearBtn) {
                    if (this.searchQuery.length > 0) {
                        clearBtn.classList.remove('d-none');
                    } else {
                        clearBtn.classList.add('d-none');
                    }
                }
                this.updateSearchResults();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.searchQuery = '';
                if (searchInput) searchInput.value = '';
                clearBtn.classList.add('d-none');
                this.updateSearchResults();
            });
        }

        // Apex Root Click & Inspect
        if (rootNode) {
            rootNode.addEventListener('click', () => {
                this.showNodeModal('root', this.config.root);
            });
        }

        // Wing Node Click
        wingCards.forEach(wing => {
            wing.addEventListener('click', (e) => {
                e.stopPropagation();
                const wingId = wing.getAttribute('data-wing-id');
                const wingObj = this.config.wings.find(w => w.id === wingId);
                if (wingObj) {
                    this.showNodeModal('wing', wingObj);
                }
            });
        });

        // Subchapter Chip Click
        nodeChips.forEach(chip => {
            chip.addEventListener('click', (e) => {
                e.stopPropagation();
                const wingId = chip.getAttribute('data-wing-id');
                const chapId = chip.getAttribute('data-chapter-id');
                const wingObj = this.config.wings.find(w => w.id === wingId);
                if (wingObj) {
                    const chapObj = wingObj.chapters.find(c => c.id === chapId);
                    if (chapObj) {
                        this.showNodeModal('chapter', chapObj, wingObj);
                    }
                }
            });
        });

        // Modal Close
        if (modalClose) {
            modalClose.addEventListener('click', () => this.hideNodeModal());
        }
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', (e) => {
                if (e.target === modalBackdrop) {
                    this.hideNodeModal();
                }
            });
        }
    }

    updateSearchResults() {
        const chips = this.container.querySelectorAll('.org-node-chip');
        chips.forEach(chip => {
            const wingId = chip.getAttribute('data-wing-id');
            const chapId = chip.getAttribute('data-chapter-id');
            const wingObj = this.config.wings.find(w => w.id === wingId);
            if (wingObj) {
                const chapObj = wingObj.chapters.find(c => c.id === chapId);
                if (chapObj) {
                    const match = this.matchesSearch(chapObj);
                    if (match) {
                        chip.style.opacity = '1';
                        chip.style.borderColor = '#2563eb';
                        chip.style.boxShadow = '0 0 10px rgba(37, 99, 235, 0.2)';
                    } else {
                        chip.style.opacity = '0.3';
                        chip.style.borderColor = '#e2e8f0';
                        chip.style.boxShadow = 'none';
                    }
                }
            }
        });
    }

    showNodeModal(type, data, parentWing = null) {
        const backdrop = this.container.querySelector('#orgTreeModalBackdrop');
        const modalContent = this.container.querySelector('#orgTreeModalContent');
        if (!backdrop || !modalContent) return;

        let contentHtml = '';
        const accentColor = data.accentColor || (parentWing ? parentWing.accentColor : '#f59e0b');

        if (type === 'root') {
            contentHtml = `
                <div class="org-modal-header text-center mb-3">
                    <div class="modal-icon-badge mx-auto mb-3" style="background:${data.accentColor}; width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 20px rgba(245,158,11,0.25);">
                        <i class="bi ${data.icon} text-white fs-3"></i>
                    </div>
                    <span class="badge" style="background: #fffbeb !important; color: #b45309 !important; border: 1px solid #fde68a !important; font-weight: 800; text-transform: uppercase; padding: 5px 14px; border-radius: 50px; font-size: 0.74rem;">
                        ${data.badge} • ${data.session}
                    </span>
                    <h4 class="text-dark fw-bold mb-1 mt-2" style="color: #0f172a !important; font-size: 1.45rem;">${data.name}</h4>
                    <p class="text-secondary small mb-0" style="color: #64748b !important;">${data.subtitle}</p>
                </div>
                <div class="org-modal-body mb-3">
                    <p class="small mb-3" style="color: #334155 !important; line-height: 1.6;">${data.desc}</p>
                    
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #b45309 !important; font-size: 0.68rem;"><i class="bi bi-person-badge me-1"></i> Patron & Lead Faculty</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.faculty}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #0284c7 !important; font-size: 0.68rem;"><i class="bi bi-buildings me-1"></i> Institutional Oversight</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.dean}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="org-modal-footer d-flex align-items-center justify-content-end gap-2">
                    <a href="${data.url}" class="btn btn-sm fw-bold px-4 text-white" style="background: #d97706 !important; border-radius: 50px; font-weight: 800;">
                        Go to Main Portal <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            `;
        } else if (type === 'wing') {
            contentHtml = `
                <div class="org-modal-header mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-icon-badge" style="background:${data.accentColor}; width: 52px; height: 52px; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink:0; color:#fff;">
                            <i class="bi ${data.icon} fs-3"></i>
                        </div>
                        <div>
                            <span class="badge mb-1" style="background: #f1f5f9 !important; color: #2563eb !important; border-radius: 50px; padding: 3px 10px; font-size: 0.7rem; text-transform: uppercase;">
                                ${data.tagline}
                            </span>
                            <h4 class="text-dark fw-bold m-0" style="color: #0f172a !important; font-size: 1.4rem;">${data.name}</h4>
                        </div>
                    </div>
                </div>
                <div class="org-modal-body mb-3">
                    <p class="small mb-3" style="color: #334155 !important; line-height: 1.6;">${data.desc}</p>
                    
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #2563eb !important; font-size: 0.68rem;"><i class="bi bi-person-badge me-1"></i> Faculty In-charge</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.faculty}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #16a34a !important; font-size: 0.68rem;"><i class="bi bi-people me-1"></i> Wing Strength</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.members} (${data.chapters.length} Chapters)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="org-modal-footer d-flex align-items-center justify-content-end gap-2">
                    <a href="${data.url}" class="btn btn-sm text-white fw-bold px-4" style="background:${data.accentColor} !important; border-radius: 50px;">
                        Visit Wing Page <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            `;
        } else if (type === 'chapter') {
            contentHtml = `
                <div class="org-modal-header mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="modal-icon-badge" style="background:${accentColor}; width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink:0; color:#fff;">
                            <i class="bi ${data.icon} fs-3"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge" style="background: #f1f5f9 !important; color: #475569 !important; border-radius: 50px; padding: 2px 8px; font-size: 0.68rem;">${parentWing ? parentWing.name : 'Wing Chapter'}</span>
                                <span class="badge" style="background:${accentColor} !important; color: #ffffff !important; border-radius: 50px; padding: 2px 8px; font-size: 0.68rem;">${data.badge}</span>
                            </div>
                            <h4 class="text-dark fw-bold m-0" style="color: #0f172a !important; font-size: 1.35rem;">${data.name}</h4>
                            <div style="color: #64748b !important; font-size: 0.78rem;">${data.category}</div>
                        </div>
                    </div>
                </div>
                <div class="org-modal-body mb-3">
                    <p class="small mb-3" style="color: #334155 !important; line-height: 1.6;">${data.desc}</p>
                    
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #2563eb !important; font-size: 0.68rem;"><i class="bi bi-person-badge me-1"></i> Faculty Mentor</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.faculty || 'Faculty Incharge'}</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-2.5 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="fw-bold text-uppercase mb-0.5" style="color: #b45309 !important; font-size: 0.68rem;"><i class="bi bi-clock-history me-1"></i> Meet Schedule</div>
                                <div class="text-dark fw-semibold small" style="color: #0f172a !important;">${data.schedule || 'Weekly Sessions'}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="org-modal-footer d-flex align-items-center justify-content-end gap-2">
                    <a href="${data.url}" class="btn btn-sm text-white fw-bold px-4" style="background:${accentColor} !important; border-radius: 50px;">
                        Explore Chapter <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
            `;
        }

        modalContent.innerHTML = contentHtml;
        backdrop.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    }

    hideNodeModal() {
        const backdrop = this.container.querySelector('#orgTreeModalBackdrop');
        if (backdrop) {
            backdrop.classList.add('d-none');
        }
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('uscOrgTreeContainer')) {
        UscOrgTree.init('uscOrgTreeContainer');
    }
});
