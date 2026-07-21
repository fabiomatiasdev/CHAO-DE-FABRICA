<?php 
    $pParam = $pageParamName ?? 'page';
    if (!empty($pagination) && isset($pagination['total']) && $pagination['total'] > 0): 
?>
<div class="pagination-wrapper">
    <div class="pagination-info">
        Mostrando <?= (($pagination['currentPage'] - 1) * $pagination['perPage']) + 1 ?>
        a <?= min($pagination['currentPage'] * $pagination['perPage'], $pagination['total']) ?>
        de <?= $pagination['total'] ?> registros
    </div>
    <nav class="pagination-nav" aria-label="Navegação por páginas">
        <?php
            $params = $_GET;
            unset($params[$pParam]);
            $queryBase = !empty($params) ? '?' . http_build_query($params) . '&' : '?';
        ?>

        <?php if ($pagination['currentPage'] > 1): ?>
            <a href="<?= $queryBase ?><?= $pParam ?>=<?= $pagination['currentPage'] - 1 ?>" class="page-btn page-prev" title="Anterior">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
        <?php else: ?>
            <span class="page-btn page-prev disabled">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </span>
        <?php endif; ?>

        <?php
            $start = max(1, $pagination['currentPage'] - 2);
            $end   = min($pagination['totalPages'], $pagination['currentPage'] + 2);
        ?>
        <?php if ($start > 1): ?>
            <a href="<?= $queryBase ?><?= $pParam ?>=1" class="page-btn">1</a>
            <?php if ($start > 2): ?><span class="page-ellipsis">...</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <?php if ($i === $pagination['currentPage']): ?>
                <span class="page-btn active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $queryBase ?><?= $pParam ?>=<?= $i ?>" class="page-btn"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $pagination['totalPages']): ?>
            <?php if ($end < $pagination['totalPages'] - 1): ?><span class="page-ellipsis">...</span><?php endif; ?>
            <a href="<?= $queryBase ?><?= $pParam ?>=<?= $pagination['totalPages'] ?>" class="page-btn"><?= $pagination['totalPages'] ?></a>
        <?php endif; ?>

        <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
            <a href="<?= $queryBase ?><?= $pParam ?>=<?= $pagination['currentPage'] + 1 ?>" class="page-btn page-next" title="Próximo">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        <?php else: ?>
            <span class="page-btn page-next disabled">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
        <?php endif; ?>
    </nav>
</div>
<?php 
    unset($pageParamName);
    endif; 
?>

