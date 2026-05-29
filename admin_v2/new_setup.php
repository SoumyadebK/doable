<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="sortable.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: #333;
        }


        /* Sidebar Styling */
        .sidebar {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 24px;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .sidebar-section-title:first-of-type {
            margin-top: 0;
        }

        .nav-link {
            color: #64748b;
            font-weight: 500;
            padding: 10px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        .nav-link.active {
            background-color: #f1f5f9;
            color: #10b981;
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        /* Main Content Area Styling */
        .main-card {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 32px;
        }

        /* Buttons & Toggles */
        .btn-success-custom {
            background-color: #22c55e;
            border-color: #22c55e;
            color: white;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 10px;
        }

        .btn-success-custom:hover {
            background-color: #16a34a;
            border-color: #16a34a;
        }

        .status-toggle-group {
            border: 1px solid #e2e8f0;
            padding: 4px;
            border-radius: 20px;
            background-color: #fff;
            display: inline-flex;
        }

        .status-btn {
            border: none;
            background: transparent;
            padding: 6px 20px;
            border-radius: 16px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #64748b;
        }

        .status-btn.active {
            background-color: #ffffff;
            color: #22c55e;
            box-shadow: 0px 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Search Input */
        .search-container {
            position: relative;
            max-width: 320px;
            width: 100%;
        }

        .search-container i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            padding-left: 40px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            height: 42px;
        }

        .search-input:focus {
            border-color: #cbd5e1;
            box-shadow: none;
        }

        /* Table Styling */
        .custom-table th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 500;
            font-size: 0.875rem;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 16px;
        }

        .custom-table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.9rem;
        }

        /* Standard Checkboxes Styling */
        .custom-table .form-check-input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
        }

        .custom-table .form-check-input[type="checkbox"]:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        /* Custom Switch / Toggle Styling */
        .custom-table .form-switch .form-check-input {
            width: 36px;
            height: 20px;
            background-color: #e2e8f0;
            border-color: #e2e8f0;
            border-radius: 20px;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        }

        .custom-table .form-switch .form-check-input:checked {
            background-color: #cbd5e1;
            border-color: #cbd5e1;
            background-position: right center;
        }

        .custom-table .form-switch .form-check-input:focus {
            box-shadow: none;
        }

        /* User Avatar */
        .avatar-circle {
            width: 40px;
            height: 40px;
            background-color: #fef08a;
            color: #854d0e;
            font-weight: 600;
            display: flex;
            align-items: cen4er;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.9rem;
            line-height: 40px;
        }

        /* Pagination Styling */
        .pagination .page-link {
            color: #64748b;
            border: 1px solid #e2e8f0;
            margin: 0 2px;
            border-radius: 20px;
            font-size: 0.875rem;
            padding: 8px 14px;
        }

        .pagination .page-item.active .page-link {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
            color: #1e293b;
        }

        .page-select {
            width: auto;
            display: inline-block;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>

    <div class="container py-4 px-4 bg-white m-3 rounded border mx-auto dashboard-container">
        <div class="row g-4">

            <div class="col-12 col-md-4 col-xl-3">
                <div class="sidebar">

                    <div class="sidebar-section-title">Operations</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link" href="#"><i class="bi bi-building"></i> Corporations</a>
                        <a class="nav-link" href="#"><i class="bi bi-geo-alt"></i> Locations</a>
                        <a class="nav-link active" href="#"><i class="bi bi-person"></i> Users</a>
                        <a class="nav-link" href="#"><i class="bi bi-journal-text"></i> Follow Ups</a>
                    </nav>

                    <div class="sidebar-section-title">Services</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link" href="#"><i class="bi bi-box-arrow-up-right"></i> Scheduling Codes</a>
                        <a class="nav-link" href="#"><i class="bi bi-handbag"></i> Services</a>
                        <a class="nav-link" href="#"><i class="bi bi-box-seam"></i> Packages</a>
                        <a class="nav-link" href="#"><i class="bi bi-file-earmark-text"></i> Document Library</a>
                    </nav>

                    <div class="sidebar-section-title">Other</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link" href="#"><i class="bi bi-gift"></i> Gift Certificates</a>
                        <a class="nav-link" href="#"><i class="bi bi-sliders2"></i> Gift Certificate Setup</a>
                        <a class="nav-link" href="#"><i class="bi bi-star"></i> Event Types</a>
                        <a class="nav-link" href="#"><i class="bi bi-book"></i> Inquiry Method</a>
                        <a class="nav-link" href="#"><i class="bi bi-dot text-secondary"></i> Lead Status</a>
                    </nav>

                    <div class="sidebar-section-title">Logs</div>
                    <nav class="nav flex-column gap-1">
                        <a class="nav-link" href="#"><i class="bi bi-chat-left-text"></i> SMS Logs</a>
                    </nav>

                </div>
            </div>

            <div class="col-12 col-md-8 col-xl-9">
                <div class="main-card">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2 class="fw-semibold h4 mb-1">Users</h2>
                            <p class="text-muted small mb-0">Optionally describe this</p>
                        </div>
                        <button class="btn btn-success-custom d-flex align-items-center gap-2">
                            <i class="bi bi-plus-lg"></i> Create New Location
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control search-input" placeholder="Search...">
                        </div>

                        <div class="status-toggle-group">
                            <button class="status-btn active">Active</button>
                            <button class="status-btn">Not Active</button>
                        </div>
                    </div>

                    <div class="text-muted small mb-3">10 active users</div>

                    <div class="table-responsive">
                        <table class="table custom-table align-middle mb-4">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Location</th>
                                    <th>Show as Recipient</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr>
                                    <td>
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">CB</div>
                                            <div>
                                                <div class="fw-semibold">Chandler Bing</div>
                                                <div class="text-muted small">chandler@alignui.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Service Provider</td>
                                    <td>Los Angeles</td>
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch">
                                        </div>
                                    </td>
                                    <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">CB</div>
                                            <div>
                                                <div class="fw-semibold">Chandler Bing</div>
                                                <div class="text-muted small">chandler@alignui.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Service Provider</td>
                                    <td>Los Angeles</td>
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch">
                                        </div>
                                    </td>
                                    <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">CB</div>
                                            <div>
                                                <div class="fw-semibold">Chandler Bing</div>
                                                <div class="text-muted small">chandler@alignui.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Service Provider</td>
                                    <td>Los Angeles</td>
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch">
                                        </div>
                                    </td>
                                    <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">CB</div>
                                            <div>
                                                <div class="fw-semibold">Chandler Bing</div>
                                                <div class="text-muted small">chandler@alignui.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Service Provider</td>
                                    <td>Los Angeles</td>
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch">
                                        </div>
                                    </td>
                                    <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                </tr>

                                <tr>
                                    <td>
                                        <div class="form-check m-0 p-0 d-flex align-items-center justify-content-center">
                                            <input class="form-check-input m-0" type="checkbox">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-circle">CB</div>
                                            <div>
                                                <div class="fw-semibold">Chandler Bing</div>
                                                <div class="text-muted small">chandler@alignui.com</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>Service Provider</td>
                                    <td>Los Angeles</td>
                                    <td>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input" type="checkbox" role="switch">
                                        </div>
                                    </td>
                                    <td><i class="bi bi-three-dots-vertical text-muted cursor-pointer"></i></td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                        <div class="text-muted small">Page 2 of 16</div>

                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0 align-items-center">
                                <li class="page-item"><a class="page-link border-0" href="#" aria-label="First"><i class="bi bi-chevron-double-left"></i></a></li>
                                <li class="page-item"><a class="page-link border-0" href="#" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>
                                <li class="page-item"><a class="page-link" href="#">1</a></li>
                                <li class="page-item active"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">4</a></li>
                                <li class="page-item"><a class="page-link" href="#">5</a></li>
                                <li class="page-item disabled"><span class="page-link border-0 bg-transparent">...</span></li>
                                <li class="page-item"><a class="page-link" href="#">16</a></li>
                                <li class="page-item"><a class="page-link border-0" href="#" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>
                                <li class="page-item"><a class="page-link border-0" href="#" aria-label="Last"><i class="bi bi-chevron-double-right"></i></a></li>
                            </ul>
                        </nav>

                        <div>
                            <select class="form-select form-select-sm page-select py-2 px-3">
                                <option selected>8 / page</option>
                                <option value="10">10 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>