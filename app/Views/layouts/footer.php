    </main>
</div>

<script>
    // Inicializar os ícones do Lucide
    lucide.createIcons();

    // Controle do Toggle da Sidebar (Botão Flutuante Notion/Linear Style)
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const toggleIcon = document.getElementById('sidebar-toggle-icon');
        
        function atualizarIcone(collapsed) {
            if (toggleIcon) {
                if (collapsed) {
                    toggleIcon.setAttribute('data-lucide', 'chevron-right');
                } else {
                    toggleIcon.setAttribute('data-lucide', 'chevron-left');
                }
                lucide.createIcons();
            }
        }

        // Ler estado inicial
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        atualizarIcone(isCollapsed);

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-collapsed');
                const collapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', collapsed ? 'true' : 'false');
                atualizarIcone(collapsed);
            });
        }
    });
</script>
</body>
</html>
