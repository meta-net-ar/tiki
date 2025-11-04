<?php

namespace Tiki\Lib\iot;

enum DrawflowNodeType
{
    case Template;
    case User_input;
    case User_choice;
    case User_hardware_io_setting;
}
