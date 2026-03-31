<?php
/** Modal de pré-visualização das imagens da OS (abre em tela grande com anterior/próxima). */
?>
<div id="lightboxGaleriaOs" class="lightbox-galeria" aria-hidden="true">
    <div class="lightbox-galeria-backdrop" data-lightbox-close="1"></div>
    <div class="lightbox-galeria-inner" role="dialog" aria-modal="true" aria-labelledby="lightboxGaleriaTitulo">
        <p id="lightboxGaleriaTitulo" class="sr-only">Pré-visualização da imagem</p>
        <button type="button" class="lightbox-galeria-close" data-lightbox-close="1" aria-label="Fechar">
            <i class="fas fa-times"></i>
        </button>
        <button type="button" class="lightbox-galeria-nav lightbox-galeria-prev" aria-label="Imagem anterior">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button type="button" class="lightbox-galeria-nav lightbox-galeria-next" aria-label="Próxima imagem">
            <i class="fas fa-chevron-right"></i>
        </button>
        <div class="lightbox-galeria-stage">
            <img src="" alt="Imagem da OS em tamanho ampliado" id="lightboxGaleriaImg">
        </div>
        <div class="lightbox-galeria-footer">
            <span id="lightboxGaleriaCounter" class="lightbox-galeria-counter"></span>
        </div>
    </div>
</div>
