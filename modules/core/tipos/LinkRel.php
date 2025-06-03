<?php

namespace modules\core\tipos;

enum LinkRel: string
{
    case SELF = 'self';
    case COLLECTION = 'collection';
    case EDIT = 'edit';
    case DELETE = 'delete';
}