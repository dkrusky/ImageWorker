# ImageWorker
PHP class to compress, scale, crop, and rotate images. Currently supported: PNG, and JPG/JPEG

### System Requirements

This class depends on *exif* and *gd* support being enabled in PHP 5.6.

You will also need to install [OptiPNG](http://optipng.sourceforge.net/) and [jpegoptim](https://github.com/tjko/jpegoptim) if you wish to use the `compress()` method.


### Methods

##### compress
> Purpose
To optimize an image in place making it as compact as possible while retaining dimenions. This will strip any exif information and automatically rotate/flip the image if required prior to removing exif meta data.

> Accepts

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|

_This method has no return value_

----
##### info
> Purpose
Obtain concise information about the image to make working with it easier.

> Accepts 

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|

> Returns : object

|Paramter|type|Description|
|---|---|---|
|width|int|The width of the image|
|height|int|The height of the image|
|ratio|int|Aspect ratio of the relationship between width and height|
|tyoe|string|Will be either 'png' or 'jpg' depending on the image type|
|larger|string|Returns a string of either 'width' or 'height' for whichever side is largest. If the ratio is even, this is '' (blank)|
|file|string|The filename without the path or extension|
|path|string|The full path of the file location with the filename stripped.|

----

##### crop
> Purpose
This method is designed to crop an image intelligently and scale it so that it fits within the dimensions specified as `width` and `height`. It also provides a method to scale the image to ideal size and then crop at the center resulting in an image zoomed, with some clipping.

> Accepts 

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|
|width|int|The width you want the new image to be|
|height|int|The height you want the new image to be|
|clip|bool|If true, perform an intelligent scaling and then crop the image out of bounds keeping the center part with no borders|

> Returns : string
String result is the full local path and filename of the new image.

----

##### scale
> Purpose
This is a wrapper for `crop()` which will determine the ideal image crop/resize based on the `max` parameter passed being the largest you want any side to be. This will calculate the requisite dimensions and run the `crop()` method

> Accepts 

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|
|max|int|The maximum you want any dimension of the image to be|
|clip|bool|If true, perform an intelligent scaling and then crop the image out of bounds keeping the center part with no borders|

> Returns : string
String result is the full local path and filename of the new image.

----

##### scaleWidth
> Purpose
This is a wrapper for `crop()` which will determine the ideal image crop/resize based on the `max` parameter passed being the largest you want the width to be. This will calculate the requisite dimensions and run the `crop()` method

> Accepts 

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|
|maxWidth|int|The maximum you want the width of the image to be|
|clip|bool|If true, perform an intelligent scaling and then crop the image out of bounds keeping the center part with no borders|

> Returns : string
String result is the full local path and filename of the new image.

----

##### scaleHeight
> Purpose
This is a wrapper for `crop()` which will determine the ideal image crop/resize based on the `max` parameter passed being the largest you want the height be. This will calculate the requisite dimensions and run the `crop()` method

> Accepts 

|Paramter|type|Description|
|---|---|---|
|file|string|The full path and filename of the image|
|maxHeight|int|The maximum you want the height of the image to be|
|clip|bool|If true, perform an intelligent scaling and then crop the image out of bounds keeping the center part with no borders|

> Returns : string
String result is the full local path and filename of the new image.
