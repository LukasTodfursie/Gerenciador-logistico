</main>

<footer class="rodape text-center py-3 mt-4">
    Projeto acadêmico em PHP
</footer>

<?php // Carrega os arquivos JavaScript que a página pediu na variável $scripts ?>
<?php if (!empty($scripts)): foreach ($scripts as $arquivo): ?>
    <script src="<?php echo $arquivo; ?>"></script>
<?php endforeach; endif; ?>

</body>
</html>