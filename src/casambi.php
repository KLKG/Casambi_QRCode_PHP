<?php
/**
 * Casambi / Lithernet gateway protocol: target types, control element types,
 * command strings and UDP sending.
 *
 * Wire format (int encoding, as parsed by the gateway's check_casambi_string()):
 *   <gateway id>#114#<length>#<opcode>#<param>#...  terminated by CR LF
 * 16-bit values (fade time, Kelvin, hue) are always sent as explicit low/high bytes.
 */

declare(strict_types=1);

const TARGET_BROADCAST    = 0;
const TARGET_DEVICE       = 1;
const TARGET_GROUP        = 2;
const TARGET_SCENE_ACTIVE = 3;
const TARGET_SCENE_ALL    = 4;
const TARGET_VENDOR_ID    = 5;
const TARGET_MULTICAST    = 8;

const OPCODE_PUSHBUTTON_PRESSED  = 16;
const OPCODE_PUSHBUTTON_RELEASED = 17;
const OPCODE_SET_SCENE_LEVEL     = 30;
const OPCODE_SET_GROUP_LEVEL     = 31;
const OPCODE_SET_LEVEL           = 32;
const OPCODE_SET_PUSHBUTTON_LEVEL = 33;
const OPCODE_SET_RGBW            = 47;
const OPCODE_SET_VERTICAL        = 49;
const OPCODE_SET_COLOR_XY        = 56;
const OPCODE_SET_HUE_SAT         = 61;
const OPCODE_SET_DIMMERS         = 62;
const OPCODE_SET_ELEMENTS        = 63;
const OPCODE_SET_COLOR_TEMP      = 72;
const OPCODE_RESUME_AUTOMATION   = 74;

/** @return array<int, string> */
function targetTypes(): array
{
    return [
        TARGET_BROADCAST    => t('Broadcast'),
        TARGET_DEVICE       => t('Device'),
        TARGET_GROUP        => t('Group'),
        TARGET_SCENE_ACTIVE => t('Scene Active'),
        TARGET_SCENE_ALL    => t('Scene All'),
        TARGET_VENDOR_ID    => t('Vendor ID'),
        TARGET_MULTICAST    => t('Multicast (Unit Set)'),
    ];
}

function targetTypeName(int $type): string
{
    return targetTypes()[$type] ?? t('Unknown');
}

/**
 * Registry of control element types.
 *
 * kind     'slider'  -> one form with one or more range inputs (fields)
 *          'buttons' -> one form with buttons (buttons); pressing one sends a fixed value
 * fields   field => [label, min, max]; min/max may be 'param_min' / 'param_max' (resolved per element)
 * buttons  list of [label, field|null, value|'param_level'|null]
 * scene    true: target_id is the scene number, target_type is irrelevant
 * target   true: element uses target type / id
 * fade     true: element uses fade_ms
 * range    true: element uses param_min / param_max (Kelvin)
 * level    true: element uses param_level
 *
 * @return array<string, array<string, mixed>>
 */
function elementTypes(): array
{
    return [
        'level' => [
            'label'  => t('Level slider'),
            'help'   => t('Brightness 0-254 (SetLevel).'),
            'kind'   => 'slider',
            'fields' => ['level' => [t('Level'), 0, 254]],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'switch' => [
            'label'   => t('On / Off buttons'),
            'help'    => t('Two buttons sending level 254 and 0 (SetLevel).'),
            'kind'    => 'buttons',
            'buttons' => [[t('On'), 'level', 254], [t('Off'), 'level', 0]],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'tc' => [
            'label'  => t('Colour temperature slider'),
            'help'   => t('Kelvin between min and max (SetTargetColorTemperature).'),
            'kind'   => 'slider',
            'fields' => ['tc' => [t('Kelvin'), 'param_min', 'param_max']],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => true, 'level' => false,
        ],
        'rgbw' => [
            'label'  => t('RGBW sliders'),
            'help'   => t('Red, green, blue and white 0-254 (SetTargetColorRGBW).'),
            'kind'   => 'slider',
            'fields' => [
                'red'   => [t('Red'), 0, 254],
                'green' => [t('Green'), 0, 254],
                'blue'  => [t('Blue'), 0, 254],
                'white' => [t('White'), 0, 254],
            ],
            'target' => true, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'huesat' => [
            'label'  => t('Hue / saturation sliders'),
            'help'   => t('Hue 0-359, saturation and white 0-254 (SetTargetColorHueSat).'),
            'kind'   => 'slider',
            'fields' => [
                'hue'   => [t('Hue'), 0, 359],
                'sat'   => [t('Saturation'), 0, 254],
                'white' => [t('White'), 0, 254],
            ],
            'target' => true, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'vertical' => [
            'label'  => t('Direct / indirect slider'),
            'help'   => t('Vertical ratio 0-254 (SetTargetVertical).'),
            'kind'   => 'slider',
            'fields' => ['vertical' => [t('Direct / indirect'), 0, 254]],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'scene' => [
            'label'  => t('Scene level slider'),
            'help'   => t('Level 0-254 of a scene; scene number = target id (SetSceneLevel).'),
            'kind'   => 'slider',
            'fields' => ['level' => [t('Level'), 0, 254]],
            'target' => false, 'fade' => true, 'scene' => true, 'range' => false, 'level' => false,
            'id_label' => t('Scene number'),
        ],
        'scene_button' => [
            'label'   => t('Scene button'),
            'help'    => t('One button activating a scene with a fixed level (SetSceneLevel).'),
            'kind'    => 'buttons',
            'buttons' => [[t('Activate'), 'level', 'param_level']],
            'target' => false, 'fade' => true, 'scene' => true, 'range' => false, 'level' => true,
            'id_label' => t('Scene number'),
        ],
        'group_level' => [
            'label'  => t('Group level slider'),
            'help'   => t('Level 0-254 of a Casambi group; group number = target id (SetGroupLevel).'),
            'kind'   => 'slider',
            'fields' => ['level' => [t('Level'), 0, 254]],
            'target' => false, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
            'id_label' => t('Group number'),
        ],
        'resume' => [
            'label'   => t('Resume automation button'),
            'help'    => t('Hands the target back to its Casambi automation (ResumeAutomation).'),
            'kind'    => 'buttons',
            'buttons' => [[t('Automatic'), null, null]],
            'target' => true, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'dimmer' => [
            'label'  => t('Dimmer slider'),
            'help'   => t('Level 0-254 of one dimmer of the target, selected by the element index (SetTargetDimmers).'),
            'kind'   => 'slider',
            'fields' => ['dimmer' => [t('Level'), 0, 254]],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
            'index'  => true,
        ],
        'element' => [
            'label'  => t('Element slider'),
            'help'   => t('Value 0-254 of one element of the target (e.g. a colour channel or custom element), selected by the element index (SetTargetElements).'),
            'kind'   => 'slider',
            'fields' => ['value' => [t('Value'), 0, 254]],
            'target' => true, 'fade' => true, 'scene' => false, 'range' => false, 'level' => false,
            'index'  => true,
        ],
        'xy' => [
            'label'  => t('Colour XY sliders'),
            'help'   => t('CIE 1931 colour point; x and y as 16-bit values 0-65535 (SetTargetColorXY).'),
            'kind'   => 'slider',
            'fields' => ['x' => [t('x'), 0, 65535], 'y' => [t('y'), 0, 65535]],
            'target' => true, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
        ],
        'pushbutton' => [
            'label'   => t('Push button'),
            'help'    => t('Behaves like a Casambi push button: "pressed" is sent while the button is held, "released" when it is let go; button id = target id (PushButtonPressed / PushButtonReleased).'),
            'kind'    => 'buttons',
            'buttons' => [[t('Push'), null, null], [t('Release'), null, null]],
            'hold'    => true,
            'target' => false, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
            'id_label' => t('Button id'),
        ],
        'pushbutton_level' => [
            'label'  => t('Push button level slider'),
            'help'   => t('Sets the level 0-254 stored for a Casambi push button; button id = target id (SetPushButtonLevel).'),
            'kind'   => 'slider',
            'fields' => ['level' => [t('Level'), 0, 254]],
            'target' => false, 'fade' => false, 'scene' => false, 'range' => false, 'level' => false,
            'id_label' => t('Button id'),
        ],
    ];
}

/** @return array<string, mixed>|null */
function elementTypeDef(string $type): ?array
{
    return elementTypes()[$type] ?? null;
}

function elementTypeLabel(string $type): string
{
    return elementTypes()[$type]['label'] ?? $type;
}

/**
 * Slider fields of an element with min/max resolved.
 *
 * @return array<string, array{label: string, min: int, max: int}>
 */
function elementFields(array $element): array
{
    $def = elementTypeDef((string) $element['element_type']);
    if ($def === null || $def['kind'] !== 'slider') {
        return [];
    }
    $out = [];
    foreach ($def['fields'] as $field => [$label, $min, $max]) {
        $out[$field] = [
            'label' => $label,
            'min'   => is_string($min) ? (int) $element[$min] : (int) $min,
            'max'   => is_string($max) ? (int) $element[$max] : (int) $max,
        ];
    }
    return $out;
}

/**
 * Buttons of an element with values resolved.
 *
 * @return list<array{label: string, field: ?string, value: ?int}>
 */
function elementButtons(array $element): array
{
    $def = elementTypeDef((string) $element['element_type']);
    if ($def === null || $def['kind'] !== 'buttons') {
        return [];
    }
    $out = [];
    foreach ($def['buttons'] as [$label, $field, $value]) {
        $out[] = [
            'label' => $label,
            'field' => $field,
            'value' => is_string($value) ? (int) $element[$value] : $value,
        ];
    }
    return $out;
}

/** @return array<string, int> */
function elementDefaultState(array $element): array
{
    $state = [];
    foreach (elementFields($element) as $field => $f) {
        $state[$field] = $field === 'sat' ? $f['max'] : $f['min'];
    }
    foreach (elementButtons($element) as $b) {
        if ($b['field'] !== null && !isset($state[$b['field']])) {
            $state[$b['field']] = 0;
        }
    }
    return $state;
}

/**
 * Decode the stored JSON state, fill defaults and clamp to the element's ranges.
 *
 * @return array<string, int>
 */
function elementState(array $element): array
{
    $state  = elementDefaultState($element);
    $stored = json_decode((string) ($element['state'] ?? ''), true);
    if (!is_array($stored)) {
        return $state;
    }
    $fields = elementFields($element);
    foreach ($state as $field => $default) {
        if (!isset($stored[$field]) || !is_numeric($stored[$field])) {
            continue;
        }
        $value = (int) $stored[$field];
        if (isset($fields[$field])) {
            $value = max($fields[$field]['min'], min($fields[$field]['max'], $value));
        } else {
            $value = max(0, min(254, $value));
        }
        $state[$field] = $value;
    }
    return $state;
}

/**
 * Build the gateway command for an element and its (new) state.
 *
 * @param array<string, int> $state
 * @param int|null $action index of the pressed button for button elements (null for sliders)
 */
function buildElementCommand(array $element, array $state, int $lithernetId, ?int $action = null): string
{
    $tt    = (int) $element['target_type'];
    $tid   = (int) $element['target_id'];
    $idx   = max(0, min(255, (int) ($element['param_index'] ?? 0)));
    $fade  = max(0, min(65535, (int) $element['fade_ms']));
    $fLow  = $fade & 0xFF;
    $fHigh = $fade >> 8;
    $gw    = $lithernetId;

    switch ((string) $element['element_type']) {
        case 'group_level':
            $level = $state['level'] ?? 0;
            return "{$gw}#114#5#" . OPCODE_SET_GROUP_LEVEL . "#{$tid}#{$level}#{$fLow}#{$fHigh}\r\n";

        case 'dimmer':
            $v = $state['dimmer'] ?? 0;
            return "{$gw}#114#7#" . OPCODE_SET_DIMMERS . "#{$tt}#{$tid}#{$fLow}#{$fHigh}#{$idx}#{$v}\r\n";

        case 'element':
            $v = $state['value'] ?? 0;
            return "{$gw}#114#7#" . OPCODE_SET_ELEMENTS . "#{$tt}#{$tid}#{$fLow}#{$fHigh}#{$idx}#{$v}\r\n";

        case 'xy':
            $x = $state['x'] ?? 0;
            $y = $state['y'] ?? 0;
            return "{$gw}#114#7#" . OPCODE_SET_COLOR_XY . '#' . ($x & 0xFF) . '#' . ($x >> 8)
                . '#' . ($y & 0xFF) . '#' . ($y >> 8) . "#{$tt}#{$tid}\r\n";

        case 'pushbutton':
            $op = $action === 1 ? OPCODE_PUSHBUTTON_RELEASED : OPCODE_PUSHBUTTON_PRESSED;
            return "{$gw}#114#2#{$op}#{$tid}\r\n";

        case 'pushbutton_level':
            $level = $state['level'] ?? 0;
            return "{$gw}#114#3#" . OPCODE_SET_PUSHBUTTON_LEVEL . "#{$tid}#{$level}\r\n";

        case 'level':
        case 'switch':
            $level = $state['level'] ?? 0;
            return "{$gw}#114#6#" . OPCODE_SET_LEVEL . "#{$level}#{$fLow}#{$fHigh}#{$tt}#{$tid}\r\n";

        case 'tc':
            $k = $state['tc'] ?? 0;
            return "{$gw}#114#7#" . OPCODE_SET_COLOR_TEMP . '#' . ($k & 0xFF) . '#' . ($k >> 8)
                . "#{$fLow}#{$fHigh}#{$tt}#{$tid}\r\n";

        case 'rgbw':
            // Last parameter 255 = keep the current level (as in the original tool).
            return "{$gw}#114#8#" . OPCODE_SET_RGBW . "#{$state['red']}#{$state['green']}#{$state['blue']}#{$state['white']}"
                . "#{$tt}#{$tid}#255\r\n";

        case 'huesat':
            $h = $state['hue'] ?? 0;
            return "{$gw}#114#8#" . OPCODE_SET_HUE_SAT . '#' . ($h & 0xFF) . '#' . ($h >> 8)
                . "#{$state['sat']}#{$state['white']}#{$tt}#{$tid}#255\r\n";

        case 'vertical':
            $v = $state['vertical'] ?? 0;
            return "{$gw}#114#6#" . OPCODE_SET_VERTICAL . "#{$v}#{$fLow}#{$fHigh}#{$tt}#{$tid}\r\n";

        case 'scene':
        case 'scene_button':
            $level = $state['level'] ?? 0;
            return "{$gw}#114#5#" . OPCODE_SET_SCENE_LEVEL . "#{$tid}#{$level}#{$fLow}#{$fHigh}\r\n";

        case 'resume':
            return "{$gw}#114#3#" . OPCODE_RESUME_AUTOMATION . "#{$tt}#{$tid}\r\n";
    }
    return '';
}

function isDemoMode(): bool
{
    return (($GLOBALS['config']['operation_mode'] ?? 'demo') !== 'run');
}

/**
 * Send a command to the gateway via UDP broadcast.
 * In demo mode nothing is sent and true is returned.
 *
 * @throws RuntimeException when the socket cannot be created
 */
function sendCasambiCommand(string $message): bool
{
    if ($message === '') {
        return false;
    }
    if (isDemoMode()) {
        return true;
    }

    $target = $GLOBALS['config']['lithernet'];
    $sock   = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($sock === false) {
        throw new RuntimeException('Could not create UDP socket: ' . socket_strerror(socket_last_error()));
    }

    try {
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        $sent = socket_sendto(
            $sock,
            $message,
            strlen($message),
            0,
            (string) $target['broadcast_ip'],
            (int) $target['port']
        );
        if ($sent === false) {
            error_log('UDP send failed: ' . socket_strerror(socket_last_error($sock)));
            return false;
        }
        return true;
    } finally {
        socket_close($sock);
    }
}
