<?php
    session_start();
    include '../database/connectdatabase.php';
    $dbname="project";
    mysqli_select_db($conn,$dbname);
    
    // Validate and sanitize input parameters
    if (!isset($_GET['user_id']) || !isset($_GET['job_id'])) {
        $_SESSION['message'] = "Missing required parameters";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit();
    }
    
    $user_id = intval($_GET['user_id']);
    $job_id = intval($_GET['job_id']);
    
    // Verify user is logged in
    if(!isset($_SESSION['user_id']))
    {
        header('Location: ../login/loginvalidation.php');
        exit();
    }
    
    // Verify the logged-in user matches the requested user_id
    if ($_SESSION['user_id'] != $user_id) {
        $_SESSION['message'] = "Unauthorized access";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit();
    }
    
    // Check if user has already applied for this job
    $check_sql = "SELECT * FROM tbl_applications WHERE user_id = ? AND job_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $job_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['message'] = "You have already applied for this job";
        $_SESSION['message_type'] = "error";
        header('Location: userdashboard.php');
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Certificate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .upload-container {
            background-color: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .upload-header {
            background-color: #9747FF;
            color: white;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .header-text h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .header-text p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .upload-body {
            padding: 32px;
        }
        
        .upload-field {
            margin-bottom: 24px;
        }
        
        .field-label {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .label-icon {
            width: 32px;
            height: 32px;
            background: #9747FF;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .label-text h3 {
            font-size: 16px;
            color: #1a202c;
            margin-bottom: 4px;
        }
        
        .label-text p {
            font-size: 14px;
            color: #718096;
        }
        
        .file-drop-area {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .file-drop-area:hover, .file-drop-area.dragover {
            border-color: #9747FF;
            background: rgba(151, 71, 255, 0.05);
        }
        
        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .file-msg {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .file-msg i {
            font-size: 32px;
            color: #9747FF;
        }
        
        .upload-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .primary-text {
            font-size: 16px;
            color: #1a202c;
            font-weight: 500;
        }
        
        .secondary-text {
            font-size: 14px;
            color: #718096;
        }
        
        .browse-text {
            color: #9747FF;
            font-weight: 500;
            cursor: pointer;
        }
        
        .file-types {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 8px;
        }
        
        .file-preview {
            display: none;
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            justify-content: space-between;
            align-items: center;
        }
        
        .preview-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .preview-content i {
            font-size: 24px;
            color: #9747FF;
        }
        
        .file-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .file-name {
            font-size: 14px;
            color: #1a202c;
            font-weight: 500;
        }
        
        .file-size {
            font-size: 12px;
            color: #718096;
        }
        
        .remove-file {
            background: none;
            border: none;
            color: #e53e3e;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .remove-file:hover {
            background: #fff5f5;
        }
        
        .error-message {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 8px;
            min-height: 20px;
        }
        
        .upload-footer {
            padding: 24px 32px;
            border-top: 1px solid #edf2f7;
            display: flex;
            justify-content: flex-end;
            gap: 16px;
        }
        
        .cancel-btn, .submit-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cancel-btn {
            background: #f7fafc;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        
        .cancel-btn:hover {
            background: #edf2f7;
        }
        
        .submit-btn {
            background: #9747FF;
            color: white;
            border: none;
        }
        
        .submit-btn:hover {
            background: #8033ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(151, 71, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="upload-header">
            <div class="header-icon">
                <i class="fas fa-file-upload"></i>
            </div>
            <div class="header-text">
                <h1>Upload Required Documents</h1>
                <p>Please provide the necessary documents to complete your application</p>
            </div>
        </div>
        
        <form action="process_documents.php" method="post" enctype="multipart/form-data">
            <!-- Important: Include the job_id and user_id as hidden fields -->
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            
            <div class="upload-body">
                <div class="upload-field">
                    <div class="field-label">
                        <div class="label-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="label-text">
                            <h3>Driving License</h3>
                            <p>Upload a clear copy of your valid driving license</p>
                        </div>
                    </div>
                    <div class="file-drop-area" id="licenseDropArea">
                        <input type="file" name="license" id="license" class="file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileSelect(this, 'license')">
                        <div class="file-msg">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div class="upload-text">
                                <span class="primary-text">Drag & drop your file here</span>
                                <span class="secondary-text">or <span class="browse-text">browse files</span></span>
                            </div>
                            <span class="file-types">Supported formats: PDF, JPG, PNG (max. 5MB)</span>
                        </div>
                    </div>
                    <div id="licensePreview" class="file-preview">
                        <div class="preview-content">
                            <i class="fas fa-file-alt"></i>
                            <div class="file-details">
                                <span class="file-name"></span>
                                <span class="file-size"></span>
                            </div>
                        </div>
                        <button type="button" class="remove-file" onclick="removeFile('license')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="error-message" id="licenseError"></div>
                </div>
                
                <div class="upload-field">
                    <div class="field-label">
                        <div class="label-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="label-text">
                            <h3>Badge Certificate</h3>
                            <p>Upload your current badge certificate</p>
                        </div>
                    </div>
                    <div class="file-drop-area" id="badgeDropArea">
                        <input type="file" name="badge" id="badge" class="file-input" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileSelect(this, 'badge')">
                        <div class="file-msg">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div class="upload-text">
                                <span class="primary-text">Drag & drop your file here</span>
                                <span class="secondary-text">or <span class="browse-text">browse files</span></span>
                            </div>
                            <span class="file-types">Supported formats: PDF, JPG, PNG (max. 5MB)</span>
                        </div>
                    </div>
                    <div id="badgePreview" class="file-preview">
                        <div class="preview-content">
                            <i class="fas fa-file-alt"></i>
                            <div class="file-details">
                                <span class="file-name"></span>
                                <span class="file-size"></span>
                            </div>
                        </div>
                        <button type="button" class="remove-file" onclick="removeFile('badge')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="error-message" id="badgeError"></div>
                </div>
            </div>
            
            <div class="upload-footer">
                <a href="userdashboard.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="submit-btn" onclick="return validateForm()">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
    
    <script>
        function handleFileSelect(input, type) {
            const file = input.files[0];
            const preview = document.getElementById(`${type}Preview`);
            const dropArea = document.getElementById(`${type}DropArea`);
            const error = document.getElementById(`${type}Error`);
            
            // Clear previous error
            error.textContent = '';
            
            if (!file) {
                preview.style.display = 'none';
                dropArea.style.display = 'block';
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                error.textContent = 'File size must be less than 5MB';
                input.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                error.textContent = 'Please upload a PDF, JPG, or PNG file';
                input.value = '';
                return;
            }
            
            // Update preview
            const fileName = preview.querySelector('.file-name');
            const fileSize = preview.querySelector('.file-size');
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            preview.style.display = 'flex';
            dropArea.style.display = 'none';
        }
        
        function removeFile(type) {
            const input = document.getElementById(type);
            const preview = document.getElementById(`${type}Preview`);
            const dropArea = document.getElementById(`${type}DropArea`);
            
            input.value = '';
            preview.style.display = 'none';
            dropArea.style.display = 'block';
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function validateForm() {
            let isValid = true;
            
            // Check license
            const licenseInput = document.getElementById('license');
            const licenseError = document.getElementById('licenseError');
            if (!licenseInput.files[0]) {
                licenseError.textContent = 'Please upload your driving license';
                isValid = false;
            }
            
            // Check badge
            const badgeInput = document.getElementById('badge');
            const badgeError = document.getElementById('badgeError');
            if (!badgeInput.files[0]) {
                badgeError.textContent = 'Please upload your badge certificate';
                isValid = false;
            }
            
            return isValid;
        }
        
        // Add drag and drop support
        const dropAreas = document.querySelectorAll('.file-drop-area');
        
        dropAreas.forEach(dropArea => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => {
                    dropArea.classList.add('dragover');
                });
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropArea.addEventListener(eventName, () => {
                    dropArea.classList.remove('dragover');
                });
            });
            
            dropArea.addEventListener('drop', (e) => {
                const input = dropArea.querySelector('.file-input');
                if (input) {
                    input.files = e.dataTransfer.files;
                    handleFileSelect(input, input.id);
                }
            });
        });
    </script>
</body>
</html>