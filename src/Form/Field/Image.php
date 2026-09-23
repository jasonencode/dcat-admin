<?php

namespace Dcat\Admin\Form\Field;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @method $this blur(int $amount = 1) Apply a gaussian blur filter with a optional amount on the current image. Use values between 0 and 100.
 * @method $this brightness(int $level) Changes the brightness of the current image by the given level. Use values between -100 for min. brightness. 0 for no change and +100 for max. brightness.
 * @method $this colorize(int $red, int $green, int $blue) Change the RGB color values of the current image on the given channels red, green and blue. The input values are normalized so you have to include parameters from 100 for maximum color value. 0 for no change and -100 to take out all the certain color on the image.
 * @method $this contrast(int $level) Changes the contrast of the current image by the given level. Use values between -100 for min. contrast 0 for no change and +100 for max. contrast.
 * @method $this cover(int $width, int $height, string $position = 'center') Combine cropping and resizing to format image in a smart way. The method will find the best fitting aspect ratio of your given width and height on the current image automatically, cut it out and resize it to the given dimension.
 * @method $this coverDown(int $width, int $height, string $position = 'center') Same as cover() but the final pixel size will never be larger than the original image.
 * @method $this crop(int $width, int $height, int $offset_x = 0, int $offset_y = 0) Cut out a rectangular part of the current image with given width and height. Define optional x,y coordinates to move the top-left corner of the cutout to a certain position.
 * @method $this drawCircle(int $radius, int $x, int $y, \Closure $callback = null) Draw a circle at given x, y, coordinates with given radius. You can define the appearance of the circle by an optional closure callback.
 * @method $this drawEllipse(int $width, int $height, int $x, int $y, \Closure $callback = null) Draw a colored ellipse at given x, y, coordinates. You can define width and height and set the appearance of the circle by an optional closure callback.
 * @method $this drawLine(int $x1, int $y1, int $x2, int $y2, \Closure $callback = null) Draw a line from x,y point 1 to x,y point 2 on current image. Define color and/or width of line in an optional Closure callback.
 * @method $this drawPixel(mixed $color, int $x, int $y) Draw a single pixel in given color on x, y position.
 * @method $this drawPolygon(array $points, \Closure $callback = null) Draw a colored polygon with given points. You can define the appearance of the polygon by an optional Closure callback.
 * @method $this drawRectangle(int $x1, int $y1, int $x2, int $y2, \Closure $callback = null) Draw a colored rectangle on current image with top-left corner on x,y point 1 and bottom-right corner at x,y point 2. Define the overall appearance of the shape by passing a Closure callback as an optional parameter.
 * @method $this exif(string $key = null) Read Exif meta data from current image.
 * @method $this fill(mixed $color) Fill the current image with a given color value.
 * @method $this flip() Mirror the current image horizontally.
 * @method $this flop() Mirror the current image vertically.
 * @method $this gamma(float $correction) Performs a gamma correction operation on the current image.
 * @method $this greyscale() Turns image into a greyscale version.
 * @method $this modify(\Closure $callback) Apply custom modifications to the image via a callback.
 * @method $this orient() Reads the EXIF image profile setting 'Orientation' and performs a rotation on the image to display the image correctly.
 * @method $this pixelate(int $size) Applies a pixelation effect to the current image with a given size of pixels.
 * @method $this read(mixed $source) Universal factory method to create a new image instance from source, which can be a filepath, a GD image resource, an Imagick object or a binary image data.
 * @method $this reduceColors(int $count) Method converts the existing colors of the current image into a color table with a given maximum count of colors.
 * @method $this resize(null|int $width = null, null|int $height = null) Resizes current image based on given width and/or height. Stretches the image to the desired size regardless of the original aspect ratio.
 * @method $this resizeCanvas(null|int $width = null, null|int $height = null, mixed $background = 'ffffff', string $position = 'center') Resize the boundaries of the current image to given width and height without resampling the original image.
 * @method $this resizeCanvasRelative(null|int $width = null, null|int $height = null, mixed $background = 'ffffff', string $position = 'center') Resize the boundaries of the current image relative to the original size.
 * @method $this resizeDown(null|int $width = null, null|int $height = null) Resizes current image based on given width and/or height without exceeding the original size.
 * @method $this rotate(float $angle, mixed $bgcolor = null) Rotate the current image counter-clockwise by a given angle. Optionally define a background color for the uncovered zone after the rotation.
 * @method $this scale(null|int $width = null, null|int $height = null) Resizes current image proportionally to given width and/or height. The aspect ratio is preserved.
 * @method $this scaleDown(null|int $width = null, null|int $height = null) Resizes current image proportionally without exceeding the original size.
 * @method $this sharpen(int $amount = 10) Sharpen current image with an optional amount. Use values between 0 and 100.
 * @method $this text(string $text, int $x = 0, int $y = 0, \Closure $callback = null) Write a text string to the current image at an optional x,y basepoint position. You can define more details like font-size, font-file and alignment via a callback as the fourth parameter.
 * @method $this trim(int $tolerance = 0) Removes border areas of the image on all sides that have a similar color. The tolerance determines color similarity.
 */
class Image extends File
{
    use ImageField;

    protected $rules = ['nullable', 'image'];

    protected $view = 'admin::form.file';

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        $this->setupImage();
    }

    protected function setupImage()
    {
        if (! isset($this->options['accept'])) {
            $this->options['accept'] = [];
        }

        $this->options['accept']['mimeTypes'] = 'image/*';
        $this->options['isImage'] = true;
    }

    /**
     * @param  array  $options  support:
     *                          [
     *                          'width' => 100,
     *                          'height' => 100,
     *                          'min_width' => 100,
     *                          'min_height' => 100,
     *                          'max_width' => 100,
     *                          'max_height' => 100,
     *                          'ratio' => 3/2, // (width / height)
     *                          ]
     * @return $this
     */
    public function dimensions(array $options)
    {
        if (! $options) {
            return $this;
        }

        $this->mergeOptions(['dimensions' => $options]);

        foreach ($options as $k => &$v) {
            $v = "$k=$v";
        }

        return $this->rules('dimensions:'.implode(',', $options));
    }

    /**
     * Set ratio constraint.
     *
     * @param  float  $ratio  width/height
     * @return $this
     */
    public function ratio($ratio)
    {
        if ($ratio <= 0) {
            return $this;
        }

        return $this->dimensions(['ratio' => $ratio]);
    }

    /**
     * @param  UploadedFile  $file
     */
    protected function prepareFile(UploadedFile $file)
    {
        $this->callInterventionMethods($file->getRealPath(), $file->getMimeType());

        $this->uploadAndDeleteOriginalThumbnail($file);
    }
}
