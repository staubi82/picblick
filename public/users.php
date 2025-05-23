<?php
/**
 * Fotogalerie - Benutzerliste
 *
 * Zeigt eine Liste aller Benutzer an
 */

// Konfiguration und Bibliotheken laden
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../lib/database.php';
require_once __DIR__ . '/../app/auth/auth.php';

// Authentifizierung initialisieren
$auth = new Auth();
$auth->checkSession();

// Aktuellen Benutzer abrufen
$currentUser = $auth->getCurrentUser();

// Datenbank initialisieren
$db = Database::getInstance();

// Alle Benutzer abrufen, die öffentliche Alben haben
$users = $db->fetchAll(
    "SELECT u.id, u.username, u.profile_image, 
     COUNT(DISTINCT a.id) as album_count,
     SUM(CASE WHEN i.id IS NOT NULL THEN 1 ELSE 0 END) as image_count
     FROM users u
     LEFT JOIN albums a ON u.id = a.user_id AND a.is_public = 1 AND a.deleted_at IS NULL
     LEFT JOIN images i ON a.id = i.album_id AND i.deleted_at IS NULL
     GROUP BY u.id
     HAVING album_count > 0
     ORDER BY u.username ASC"
);

// Profilbild-URLs vorbereiten
foreach ($users as &$user) {
    if (!empty($user['profile_image']) &&
        file_exists(STORAGE_PATH . '/profile_images/' . $user['profile_image'])) {
        // Benutzer hat ein eigenes Profilbild
        $user['profile_image_url'] = '/storage/profile_images/' . $user['profile_image'];
    } else {
        // Standard-Avatar verwenden oder generischen Avatar erstellen
        $defaultProfileImg = __DIR__ . '/img/default-profile.jpg';
        if (file_exists($defaultProfileImg)) {
            $user['profile_image_url'] = '/public/img/default-profile.jpg';
        } else {
            // Einen farbigen Buchstaben-Avatar erstellen
            $user['profile_image_url'] = 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="200" height="200"><rect width="100" height="100" fill="#6200ee"/><text x="50" y="60" font-family="Arial" font-size="45" fill="white" text-anchor="middle">' . strtoupper(substr($user['username'], 0, 1)) . '</text></svg>');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzer - Fotogalerie</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <style>
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .user-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .user-card a {
            display: flex;
            flex-direction: column;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }
        
        .user-avatar {
            display: flex;
            justify-content: center;
            padding: 20px 0;
            background-color: #f5f5f5;
        }
        
        .user-avatar img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            padding: 20px;
            text-align: center;
        }
        
        .user-info h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: var(--primary-text);
        }
        
        .user-stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--secondary-text);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        /* Dunkelmodus Anpassungen */
        @media (prefers-color-scheme: dark) {
            .user-card {
                background-color: #2d2d2d;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }
            
            .user-card:hover {
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            }
            
            .user-avatar {
                background-color: #1e1e1e;
            }
            
            .user-avatar img {
                border-color: #2d2d2d;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            }
            
            .user-info h3 {
                color: #e0e0e0;
            }
            
            .user-stats {
                color: #b0b0b0;
            }
            
            .empty-state {
                background-color: #2d2d2d;
                color: #e0e0e0;
            }
        }
    </style>
</head>
<body>
    <?php
    // Aktive Seite für die Navigation
    $activePage = 'users';
    
    // Header einbinden
    require_once __DIR__ . '/includes/header.php';
    ?>

    <main>
        <div class="container">
            <section>
                <div class="section-header">
                    <h2>Benutzer mit öffentlichen Alben</h2>
                </div>

                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <p>Derzeit gibt es keine Benutzer mit öffentlichen Alben.</p>
                    </div>
                <?php else: ?>
                    <div class="user-grid">
                        <?php foreach ($users as $user): ?>
                            <div class="user-card">
                                <a href="/public/user-profile.php?id=<?php echo $user['id']; ?>">
                                    <div class="user-avatar">
                                        <img src="<?php echo htmlspecialchars($user['profile_image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($user['username']); ?>">
                                    </div>
                                    <div class="user-info">
                                        <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                        <div class="user-stats">
                                            <span><?php echo $user['album_count']; ?> Alben</span>
                                            <span><?php echo $user['image_count']; ?> Bilder</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php
    // Footer einbinden
    require_once __DIR__ . '/includes/footer.php';
    ?>

    <script src="/public/js/main.js"></script>
</body>
</html>