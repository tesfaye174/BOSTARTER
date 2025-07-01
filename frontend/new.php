<?php
session_start();

// Sessione utente temporanea per testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID utente temporaneo per testing
}

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/models/Project.php';

// Controlla autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Ottieni connessione al database
$db = Database::getInstance();
$conn = $db->getConnection();

// Inizializza il modello Project
$project = new Project();

$message = '';
$error = '';

// Gestisce la creazione del progetto via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description']),
            'category' => $_POST['category'],
            'funding_goal' => floatval($_POST['funding_goal']),
            'deadline' => $_POST['deadline'],
            'creator_id' => $_SESSION['user_id']
        ];
        // Valida i dati del form
        if (empty($data['name']) || empty($data['description']) || empty($data['category']) || 
            $data['funding_goal'] <= 0 || empty($data['deadline'])) {
            throw new Exception('Tutti i campi sono obbligatori e l\'obiettivo di finanziamento deve essere maggiore di 0.');
        }
        
        // Valida la data di scadenza
        if (strtotime($data['deadline']) <= time()) {
            throw new Exception('La data di scadenza deve essere nel futuro.');
        }
        
        // Gestisce l'upload dell'immagine
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/projects/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $imageInfo = getimagesize($_FILES['image']['tmp_name']);
            if (!$imageInfo) {
                throw new Exception('File immagine non valido.');
            }
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($imageInfo['mime'], $allowedTypes)) {
                throw new Exception('Solo immagini JPEG, PNG, GIF e WebP sono consentite.');
            }
            $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('project_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $data['image'] = 'uploads/projects/' . $fileName;
            } else {
                throw new Exception('Errore durante l\'upload dell\'immagine.');
            }
        }
        
        $projectId = $project->create($data);
        if ($projectId) {
            $message = 'Progetto creato con successo!';
            header('Location: view.php?id=' . $projectId);
            exit();
        } else {
            $error = 'Errore nella creazione del progetto. Riprova.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
$categories = [
    'technology' => 'Technology',
    'art' => 'Art & Design',
    'music' => 'Music',
    'film' => 'Film & Video',
    'games' => 'Games',
    'publishing' => 'Publishing',
    'food' => 'Food & Beverage',
    'fashion' => 'Fashion',
    'health' => 'Health & Fitness',
    'education' => 'Education',
    'community' => 'Community',
    'environment' => 'Environment'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Project - BOSTARTER</title>
    <!-- Stylesheets -->
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="images/icon-144x144.png">
    <style>
    .create-project-container {
        background: linear-gradient(135deg, #f8f9ff 0%, #e8f0ff 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .project-form-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(90, 135, 250, 0.1);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-section {
        margin-bottom: 2rem;
    }

    .form-section h4 {
        color: #2C3DB2;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .form-control,
    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #5A87FA;
        box-shadow: 0 0 0 0.2rem rgba(90, 135, 250, 0.25);
    }

    .btn-create {
        background: linear-gradient(135deg, #5A87FA 0%, #2C3DB2 100%);
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(90, 135, 250, 0.3);
    }

    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        margin-top: 1rem;
        display: none;
    }

    .funding-goal-input {
        position: relative;
    }

    .funding-goal-input::before {
        content: '$';
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-weight: 500;
        z-index: 5;
    }

    .funding-goal-input input {
        padding-left: 2rem;
    }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span style="color: #5A87FA;">BO</span><span style="color: #2C3DB2;">STARTER</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dash.php">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>
    <div class="create-project-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h1 class="display-5 fw-bold" style="color: #2C3DB2;">Create Your Project</h1>
                        <p class="lead text-muted">Turn your idea into reality with the support of our community</p>
                    </div>
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    <!-- Project Form -->
                    <form method="POST" enctype="multipart/form-data" class="project-form-card">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h4><i class="bi bi-info-circle me-2"></i>Basic Information</h4>
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Project Name *</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    placeholder="Enter your project name" required
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label fw-semibold">Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5"
                                    placeholder="Describe your project, what makes it special, and why people should support it"
                                    required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="form-text">Tell your story and explain what you're trying to achieve.</div>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label fw-semibold">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Choose a category</option>
                                    <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"
                                        <?php echo (isset($_POST['category']) && $_POST['category'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <!-- Funding Details -->
                        <div class="form-section">
                            <h4><i class="bi bi-currency-dollar me-2"></i>Funding Details</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="funding_goal" class="form-label fw-semibold">Funding Goal *</label>
                                    <div class="funding-goal-input">
                                        <input type="number" class="form-control" id="funding_goal" name="funding_goal"
                                            min="1" step="0.01" placeholder="0.00" required
                                            value="<?php echo isset($_POST['funding_goal']) ? $_POST['funding_goal'] : ''; ?>">
                                    </div>
                                    <div class="form-text">Set a realistic funding goal for your project.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="deadline" class="form-label fw-semibold">Campaign Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                        value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : ''; ?>">
                                    <div class="form-text">Choose when your campaign should end.</div>
                                </div>
                            </div>
                        </div>
                        <!-- Project Image -->
                        <div class="form-section">
                            <h4><i class="bi bi-image me-2"></i>Project Image</h4>
                            <div class="mb-3">
                                <label for="image" class="form-label fw-semibold">Upload Project Image</label>
                                <input type="file" class="form-control" id="image" name="image"
                                    accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)">
                                <div class="form-text">Upload a compelling image that represents your project (JPG, PNG,
                                    GIF, WebP - Max 5MB).</div>
                                <img id="imagePreview" class="image-preview" alt="Image preview">
                            </div>
                        </div>
                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dash.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-create text-white">
                                <i class="bi bi-plus-circle me-2"></i>Create Project
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="js/app.js"></script>
    <script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
        }
    }
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const description = document.getElementById('description').value.trim();
        const category = document.getElementById('category').value;
        const fundingGoal = parseFloat(document.getElementById('funding_goal').value);
        const deadline = document.getElementById('deadline').value;
        if (!name || !description || !category || !fundingGoal || !deadline) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return;
        }
        if (fundingGoal <= 0) {
            e.preventDefault();
            alert('Funding goal must be greater than 0.');
            return;
        }
        const selectedDate = new Date(deadline);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selectedDate <= today) {
            e.preventDefault();
            alert('Deadline must be in the future.');
            return;
        }
    });
    // Character counter for description
    const descriptionTextarea = document.getElementById('description');
    const maxLength = 1000;

    function updateCharacterCount() {
        const remaining = maxLength - descriptionTextarea.value.length;
        const counter = document.getElementById('charCounter');
        if (!counter) {
            const counterDiv = document.createElement('div');
            counterDiv.id = 'charCounter';
            counterDiv.className = 'form-text text-end';
            descriptionTextarea.parentNode.appendChild(counterDiv);
        }
        document.getElementById('charCounter').textContent = `${remaining} characters remaining`;
        if (remaining < 0) {
            document.getElementById('charCounter').classList.add('text-danger');
        } else {
            document.getElementById('charCounter').classList.remove('text-danger');
        }
    }
    descriptionTextarea.addEventListener('input', updateCharacterCount);
    descriptionTextarea.setAttribute('maxlength', maxLength);
    updateCharacterCount();
    </script>
</body>

</html>