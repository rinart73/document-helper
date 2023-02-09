<?php

declare(strict_types=1);

// Example: Images - Pregeneration and delayed cron generation instead of on-demand

function defaultValues(): void
{
    service('documentImages')
        // don't generate images on demand
        ->setOnDemandGeneration(false)
        // report images that are missing
        ->setReportMissing(true)
        ->setAlternateTypes(IMAGETYPE_WEBP)
        ->setDefaultQuality(85)
        ->setDefaultImgAttributes(['class' => 'img-fluid', 'loading' => 'lazy']);
}

function whenImageIsUploaded(): void
{
    $path = '/uploads/new.jpg';

    // get separate instance to not interfere with the main context
    $images = service('documentImages', null, true);
    $images
        ->setAlternateTypes(IMAGETYPE_WEBP)
        ->setDefaultQuality(85);

    // pregenerate image data without rendering img/picture tag
    $images->buildVariants($path, ['top-left', '1:1', 1536, 1024, 768]);
}

function useInController()
{
    $result = view('someview');

    /**
     * When on demand generation is off, contains images that were needed but not found
     * In our case it will be:
     * [
     *     '/uploads/new.jpg' => [
     *         '/cache/images/uploads/new_jpg/2048x2048.jpg' => [
     *             'width'     => 2048,
     *             'height'    => 2048,
     *             'imageType' => null,
     *             'position'  => 'top-left',
     *             'quality'   => 85,
     *         ],
     *         '/cache/images/uploads/new_jpg/2048x2048.webp' => [
     *             'width'     => 2048,
     *             'height'    => 2048,
     *             'imageType' => 2,
     *             'position'  => 'top-left',
     *             'quality'   => 85,
     *         ]
     *     ]
     * ]
     */
    $missingImages = service('documentImages')->getMissing();

    saveInDBToGenerateLaterWithCron($missingImages);

    return $result;
}

function useInView(): void
{
    ?>

<?= document_image('/uploads/new.jpg', ['top-left', '1:1', 2048, 1536, 1024, 768]) ?>

<?php
}

function lateGenerationWithCron(): void
{
    $missingImages = getMissingImagesFromDB();

    // get separate instance to not interfere with the main context
    $images = service('documentImages', null, true);

    // generate missing images one by one
    foreach ($missingImages as $path => $variants) {
        foreach ($variants as $data) {
            $images->resize($path, $data['width'], $data['height'], $data['imageType'], $data['position'], $data['quality']);
        }
    }
}
?>