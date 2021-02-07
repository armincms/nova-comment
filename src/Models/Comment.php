<?php

namespace Armincms\NovaComment\Models;

use Laravelista\Comments\Comment as Model; 
use Armincms\Rating\Rateable;

class Comment extends Model
{ 
    use Rateable;
}
