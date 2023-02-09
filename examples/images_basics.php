<?php

declare(strict_types=1);

// Example: Images basics

function defaultValues(): void
{
    service('documentImages')
        ->setAlternateTypes(IMAGETYPE_WEBP)
        ->setDefaultQuality(85)
        ->setDefaultSrcsetWidths(2048, 1536, 1024, 768)
        ->setDefaultImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);
}

function useInController(): void
{
    $images = service('documentImages');

    // my.jpg is 1920x1080

    /**
     * Generates:
     * * 1536x864.jpg, 1024x576.jpg, 768x432.jpg
     * * 1920x1080.webp, 1536x864.webp, 1024x576.webp, 768x432.webp
     * Renders:
     * * sizes attribute: (max-width: 1920px) 100vw, 1920px
     * * picture tag that contains source tags and img tag
     * * img tag: width="1920" height="1080" class="img-fluid" loading="lazy"
     */
    $result = $images->renderTag('uploads/my.jpg');

    /**
     * Generates: nothing, because variants are disabled
     * Renders:
     * * img tag with: width="1920" height="1080" class="img-fluid my-img" loading="lazy"
     */
    $result = $images->renderTag('uploads/my.jpg', false, ['class' => 'img-fluid my-img']);

    /**
     * Generates:
     * * 1280x720.jpg
     * * 1920x1080.webp, 1280x720.webp
     * Renders:
     * * sizes attribute: (max-width: 1920px) 100vw, 1920px
     * * picture tag that contains source tags and img tag
     * * img tag: width="1920" height="1080" class="img-fluid"
     */
    $result = $images->renderTag('uploads/my.jpg', [1280], ['loading' => null]);

    /**
     * Generates:
     * * 1024x1024.jpg, 768x768.jpg
     * * 1024x1024.webp, 768x768.webp
     * Renders:
     * * sizes attribute: (max-width: 1024px) 100vw, 1024px
     * * picture tag that contains source tags and img tag
     * * img tag: width="1024" height="1024" class="img-fluid" loading="lazy"
     */
    $result = $images->renderTag('uploads/my.jpg', ['1:1', 'top', 1024, 768]);
}

function useInView(): void
{
    ?>

<?= document_image('uploads/my.jpg') ?>

<?= document_image('uploads/my.jpg', false, ['class' => 'img-fluid my-img']) ?>

<?= document_image('uploads/my.jpg', [1280], ['loading' => null]) ?>

<?= document_image('uploads/my.jpg', ['1:1', 'top', 1024, 768]) ?>

<?php
}
