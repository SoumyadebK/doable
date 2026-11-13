<!DOCTYPE html>
<html lang="en">
<?php include 'header_script.php'; ?>
<?php require_once('../../includes/header.php'); ?>
<?php include 'layout/header.php'; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="sortable.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }


        .dashboard-container {
            /* max-width: 1400px; */
        }

        /* Sidebar Styles */
        .sidebar-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            min-height: 100%;
        }

        .sidebar-section {
            margin-bottom: 1.5rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 0.7rem;
            font-weight: 700;
            color: #a0aec0;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            padding-left: 0.5rem;
        }

        .sidebar-card .nav-link {
            color: #4a5568;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }

        .sidebar-card .nav-link i {
            font-size: 1rem;
            color: #718096;
        }

        .sidebar-card .nav-link .dot-icon {
            font-size: 1.2rem;
            line-height: 1;
            color: #718096;
            margin-left: 2px;
            margin-right: 2px;
        }

        .sidebar-card .nav-link:hover {
            background-color: #f8fafc;
            color: #1a202c;
        }

        /* Active State for 'Follow Ups' */
        .sidebar-card .nav-link.active {
            background-color: #f1f5f9;
            color: #10b981 !important;
            /* Green icon color as per UI */
            font-weight: 600;
        }

        .sidebar-card .nav-link.active i {
            color: #10b981;
        }

        /* Main Content Area */
        .main-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .extra-small {
            font-size: 0.65rem;
            letter-spacing: 0.03em;
            font-weight: 600;
        }

        /* Automation Card Component */
        .automation-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
        }

        .icon-wrapper {
            width: 40px;
            height: 40px;
            background-color: #f1f5f9;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .icon-wrapper i {
            font-size: 1.1rem;
        }

        /* Custom Bootstrap Form Switch to match UI exactly */
        .custom-switch {
            position: relative;
        }

        .custom-switch .form-check-input {
            width: 2.5em;
            height: 1.35em;
            background-color: #cbd5e1;
            border-color: transparent;
            cursor: pointer;
        }

        .custom-switch .form-check-input:checked {
            background-color: #10b981;
            /* Green color match */
            border-color: transparent;
        }

        .custom-switch .form-check-input:focus {
            box-shadow: none;
            border-color: transparent;
        }

        /* Add Follow Up Button styling */
        .btn-add-followup {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            /* Pillow oval shape like the image */
            color: #4a5568;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .btn-add-followup:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #1a202c;
        }
    </style>
</head>

<body>

    <div class="container py-4 px-4 bg-white m-3 rounded border mx-auto dashboard-container">
        <div class="row min-vh-100 p-4 justify-content-center">

            <div class="col-12 col-md-4 col-lg-3 mb-4 mb-md-0">
                <div class="sidebar-card p-4">

                    <div class="sidebar-section">
                        <div class="section-title">OPERATIONS</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-building"></i> Corporations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-geo-alt"></i> Locations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-person"></i> Users</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="#"><i class="bi bi-journal-bookmark-fill"></i> Follow Ups</a>
                            </li>
                        </ul>
                    </div>

                    <div class="sidebar-section">
                        <div class="section-title">SERVICES</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-box-arrow-up-right"></i> Scheduling Codes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-handbag"></i> Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-box-seam"></i> Packages</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-file-earmark-text"></i> Document Library</a>
                            </li>
                        </ul>
                    </div>

                    <div class="sidebar-section">
                        <div class="section-title">OTHER</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-gift"></i> Gift Certificates</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-gear-wide"></i> Gift Certificate Setup</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-star"></i> Event Types</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-book"></i> Inquiry Method</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"><span class="dot-icon">•</span> Lead Status</a>
                            </li>
                        </ul>
                    </div>

                    <div class="sidebar-section">
                        <div class="section-title">LOGS</div>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#"><i class="bi bi-chat-left-text"></i> SMS Logs</a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

            <div class="col-12 col-md-8 col-lg-7">
                <div class="main-card p-4 h-100">

                    <div class="main-header border-bottom pb-3 mb-4">
                        <h2 class="h4 mb-1 fw-semibold text-dark">Automations</h2>
                        <p class="text-muted small mb-0">Enable automatic to-do's</p>
                    </div>

                    <div class="automation-card p-3 mb-3 d-flex align-items-start justify-content-between">
                        <div class="d-flex align-items-start gap-3">
                            <div class="icon-wrapper d-flex align-items-center justify-content-center">
                                <i class="bi bi-lightning-charge-fill text-secondary"></i>
                            </div>
                            <div>
                                <h3 class="h6 mb-1 fw-semibold text-dark">Trial Class Follow Up</h3>
                                <p class="text-muted small mb-1">When a customer completes a class and has not purchased a contract</p>
                                <span class="text-uppercase text-muted extra-small">EDITED 1 DAY AGO</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 pt-1">
                            <div class="form-check form-switch custom-switch d-flex align-items-center gap-2 m-0 p-0">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="switch1" checked>
                                <label class="form-check-label text-dark small fw-medium" for="switch1">On</label>
                            </div>
                            <button class="btn btn-link text-muted p-0 border-0"><i class="bi bi-chevron-right fs-5"></i></button>
                        </div>
                    </div>

                    <button class="btn btn-add-followup w-100 py-2.5 fw-medium">
                        Add Follow Up
                    </button>

                </div>
            </div>

        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>