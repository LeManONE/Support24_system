<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
requireLogin();

$conn = db();
$articles = $conn->query("
    SELECT * FROM faq 
    ORDER BY sort_order ASC, created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<?php include '../../includes/header.php'; ?>

<div class="faq-page">
    <h1>📚 Частые проблемы и решения</h1>
    
    <div class="faq-grid">
        <?php if (empty($articles)): ?>
            <div class="empty-state">
                <p>Пока нет статей</p>
            </div>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
            <a href="<?php echo htmlspecialchars($article['article_file']); ?>" 
               class="faq-card" target="_blank">
                <div class="faq-image">
                    <?php if ($article['image_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $article['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($article['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($article['title']); ?>">
                    <?php else: ?>
                        <div class="no-image">📄</div>
                    <?php endif; ?>
                </div>
                <div class="faq-content">
                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                    <?php if ($article['description']): ?>
                        <p><?php echo htmlspecialchars(truncate($article['description'], 100)); ?></p>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.faq-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.faq-page h1 {
    text-align: center;
    margin-bottom: 40px;
    color: #333;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
}

.faq-card {
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.faq-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(102,126,234,0.3);
}

.faq-image {
    height: 180px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.faq-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    font-size: 48px;
    color: #999;
}

.faq-content {
    padding: 20px;
}

.faq-content h3 {
    margin-bottom: 10px;
    color: #333;
}

.faq-content p {
    color: #666;
    line-height: 1.5;
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 10px;
    color: #999;
}
</style>

<?php include '../../includes/footer.php'; ?>
