<?php
require_once '../../includes/auth.php';
requireAdmin();

$grafana_url = 'http://' . $_SERVER['SERVER_ADDR'] . ':3000';
?>
<?php include '../../includes/header.php'; ?>

<div class="monitoring-page">
    <h1>📊 Мониторинг клиентов</h1>
    
    <div class="grafana-embed">
        <iframe src="<?php echo $grafana_url; ?>/d/1860/node-exporter-full?orgId=1&kiosk&refresh=30s" 
                width="100%" 
                height="900" 
                frameborder="0"
                allow="fullscreen">
        </iframe>
    </div>
    
    <div class="monitoring-info">
        <h3>🔍 Информация:</h3>
        <ul>
            <li><strong>Prometheus:</strong> <a href="http://<?php echo $_SERVER['SERVER_ADDR']; ?>:9090" target="_blank">http://<?php echo $_SERVER['SERVER_ADDR']; ?>:9090</a></li>
            <li><strong>Grafana:</strong> <a href="<?php echo $grafana_url; ?>" target="_blank"><?php echo $grafana_url; ?></a></li>
            <li><strong>Node Exporter порт:</strong> 9100</li>
        </ul>
    </div>
</div>

<style>
.monitoring-page { padding: 20px; }
.grafana-embed {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin: 20px 0;
}
.monitoring-info {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}
.monitoring-info ul {
    margin-top: 10px;
    padding-left: 20px;
}
.monitoring-info li { margin: 10px 0; }
</style>

<?php include '../../includes/footer.php'; ?>
