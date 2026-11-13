<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

/**
 * Generates clean garment illustrations as SVG so every product has a gallery
 * (front / back / detail / folded) without needing photos. Real photos uploaded
 * from the admin panel take priority. Original artwork, no third-party assets.
 */
class ImageController
{
    /** Garment silhouettes besides the classic shirt (full / half) and shorts (none). */
    public const KINDS = ['tee', 'polo', 'hoodie', 'sweat', 'jacket', 'blazer', 'kurta', 'dress', 'trouser', 'chino', 'jeans', 'jogger'];

    public function shirt(Request $request): void
    {
        $hex = preg_replace('/[^0-9a-fA-F]/', '', (string) $request->param('hex', 'cccccc'));
        $hex = strlen($hex) === 6 ? strtolower($hex) : 'cccccc';
        $view = preg_replace('/[^a-z]/', '', preg_replace('/\.svg$/', '', (string) $request->param('view', 'front')));
        $sleeve = in_array($request->query('s'), array_merge(['half', 'none'], self::KINDS), true) ? $request->query('s') : 'full';
        $pattern = preg_replace('/[^a-z]/', '', (string) $request->query('p', ''));

        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=31536000, immutable');

        echo $this->svg($hex, $view, $sleeve, $pattern);
    }

    private function shade(string $hex, float $factor): string
    {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $mix = fn (int $c) => (int) max(0, min(255, $factor >= 0
            ? $c + (255 - $c) * $factor
            : $c * (1 + $factor)));

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
    }

    /** Fabric pattern definitions; %1$s = ink colour, %2$s = opacity. */
    private function patternDef(string $pattern): ?string
    {
        $defs = [
            'stripe' => '<pattern id="pat" width="9" height="9" patternUnits="userSpaceOnUse"><rect width="2" height="9" fill="%1$s" opacity="%2$s"/></pattern>',
            'check' => '<pattern id="pat" width="14" height="14" patternUnits="userSpaceOnUse"><path d="M0 0H14M0 0V14" stroke="%1$s" stroke-opacity="%2$s" stroke-width="1.6" fill="none"/></pattern>',
            'dots' => '<pattern id="pat" width="9" height="9" patternUnits="userSpaceOnUse"><circle cx="3" cy="3" r="1.5" fill="%1$s" opacity="%2$s"/><circle cx="7.5" cy="7.5" r="1.5" fill="%1$s" opacity="%2$s"/></pattern>',
            'rib' => '<pattern id="pat" width="8" height="5" patternUnits="userSpaceOnUse"><rect width="8" height="1.4" fill="%1$s" opacity="%2$s"/></pattern>',
            'diag' => '<pattern id="pat" width="26" height="26" patternUnits="userSpaceOnUse" patternTransform="rotate(32)"><rect width="5" height="26" fill="%1$s" opacity="%2$s"/><rect x="13" width="2" height="26" fill="%1$s" opacity="%2$s"/></pattern>',
            'floral' => '<pattern id="pat" width="34" height="34" patternUnits="userSpaceOnUse"><g fill="%1$s" opacity="%2$s"><circle cx="9" cy="9" r="3"/><circle cx="14" cy="9" r="3"/><circle cx="11.5" cy="13" r="3"/><circle cx="26" cy="25" r="2.4"/><circle cx="30" cy="25" r="2.4"/><circle cx="28" cy="28.5" r="2.4"/></g></pattern>',
            'bandana' => '<pattern id="pat" width="32" height="32" patternUnits="userSpaceOnUse"><g fill="none" stroke="%1$s" stroke-opacity="%2$s" stroke-width="1.6"><circle cx="16" cy="16" r="9"/><circle cx="16" cy="16" r="3"/></g><circle cx="2" cy="2" r="2" fill="%1$s" opacity="%2$s"/><circle cx="30" cy="30" r="2" fill="%1$s" opacity="%2$s"/></pattern>',
            'net' => '<pattern id="pat" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M0 0L8 8M8 0L0 8" stroke="%1$s" stroke-opacity="%2$s" stroke-width="1" fill="none"/></pattern>',
        ];

        return $defs[$pattern] ?? null;
    }

    private function svg(string $hex, string $view, string $sleeve, string $pattern): string
    {
        if (in_array($sleeve, self::KINDS, true)) {
            return $this->garment($hex, $view, $sleeve, $pattern);
        }

        $half = $sleeve === 'half';
        $shorts = $sleeve === 'none';

        $base = '#' . $hex;
        $dark = $this->shade($hex, -0.18);
        $darker = $this->shade($hex, -0.32);
        $light = $this->shade($hex, 0.18);
        $lum = hexdec(substr($hex, 0, 2)) * 0.299 + hexdec(substr($hex, 2, 2)) * 0.587 + hexdec(substr($hex, 4, 2)) * 0.114;
        $btn = $lum > 150 ? '#8a8a85' : '#e9e6df';
        $line = $lum > 150 ? 'rgba(0,0,0,.18)' : 'rgba(255,255,255,.22)';

        $patDef = '';
        $fill = '';
        if ($def = $this->patternDef($pattern)) {
            $patDef = sprintf($def, $lum > 150 ? '#000' : '#fff', $lum > 150 ? '.16' : '.22');
            $fill = 'url(#pat)';
        }

        $vb = match (true) {
            $shorts && $view === 'detail' => '120 200 360 300',
            $view === 'detail' => '150 70 300 380',
            default => '0 0 600 760',
        };
        [$vx, $vy, $vw, $vh] = array_map('floatval', explode(' ', $vb));

        $defs = '<defs>
            <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f6f4f0"/><stop offset="1" stop-color="#e8e4dd"/></linearGradient>
            <linearGradient id="sh" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".16"/><stop offset=".35" stop-color="#000" stop-opacity="0"/><stop offset=".7" stop-color="#fff" stop-opacity=".08"/><stop offset="1" stop-color="#000" stop-opacity=".18"/></linearGradient>
            <filter id="drop" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="0" dy="14" stdDeviation="14" flood-color="#000" flood-opacity=".16"/></filter>'
            . $patDef . '</defs>';
        $bg = "<rect x=\"{$vx}\" y=\"{$vy}\" width=\"{$vw}\" height=\"{$vh}\" fill=\"url(#bg)\"/>";

        $overlay = fn (string ...$paths) => $fill
            ? implode('', array_map(fn ($d) => "<path d=\"{$d}\" fill=\"{$fill}\"/>", $paths))
            : '';

        if ($shorts) {
            $d = 'M150 250 L450 250 L470 640 L318 640 L300 420 L282 640 L130 640 Z';
            $inner = "<g filter=\"url(#drop)\"><path d=\"{$d}\" fill=\"{$base}\"/>" . $overlay($d)
                . "<rect x=\"150\" y=\"226\" width=\"300\" height=\"36\" rx=\"6\" fill=\"{$dark}\"/>"
                . "<path d=\"M300 262 L300 420\" stroke=\"{$line}\" stroke-width=\"3\"/><circle cx=\"300\" cy=\"244\" r=\"7\" fill=\"{$btn}\"/>"
                . "<path d=\"M180 330 Q220 360 260 330\" stroke=\"{$line}\" fill=\"none\" stroke-width=\"2\"/>"
                . "<path d=\"M340 330 Q380 360 420 330\" stroke=\"{$line}\" fill=\"none\" stroke-width=\"2\"/></g>";

            return $this->wrap($vb, $defs, $bg, $inner);
        }

        $sleeveL = $half
            ? 'M196 128 L112 150 L74 318 L168 330 L186 226 Z'
            : 'M196 128 L112 150 L52 590 L124 602 L178 296 Z';
        $sleeveR = $half
            ? 'M404 128 L488 150 L526 318 L432 330 L414 226 Z'
            : 'M404 128 L488 150 L548 590 L476 602 L422 296 Z';
        $body = 'M196 128 Q300 172 404 128 L436 700 Q300 722 164 700 Z';
        $cuffs = $half ? '' :
            "<path d=\"M52 590 L124 602 L120 630 L48 618 Z\" fill=\"{$dark}\"/><path d=\"M548 590 L476 602 L480 630 L552 618 Z\" fill=\"{$dark}\"/>";
        $fabric = $overlay($body, $sleeveL, $sleeveR);

        $buttons = '';
        for ($i = 0; $i < 8; $i++) {
            $y = 222 + $i * 64;
            $buttons .= "<circle cx=\"300\" cy=\"{$y}\" r=\"6\" fill=\"{$btn}\" stroke=\"{$dark}\" stroke-width=\"1\"/>"
                . "<circle cx=\"298\" cy=\"" . ($y - 1) . "\" r=\"1.3\" fill=\"{$dark}\"/><circle cx=\"302\" cy=\"" . ($y - 1) . "\" r=\"1.3\" fill=\"{$dark}\"/>";
        }

        if ($view === 'back') {
            $inner = "<g filter=\"url(#drop)\">
                <path d=\"{$sleeveL}\" fill=\"{$base}\"/><path d=\"{$sleeveR}\" fill=\"{$base}\"/>{$cuffs}
                <path d=\"{$body}\" fill=\"{$base}\"/>{$fabric}
                <path d=\"M196 128 Q300 150 404 128 L404 190 Q300 215 196 190 Z\" fill=\"{$dark}\" opacity=\".55\"/>
                <path d=\"M300 215 L300 700\" stroke=\"{$line}\" stroke-width=\"2\"/>
                <path d=\"M262 112 Q300 128 338 112 L346 138 Q300 156 254 138 Z\" fill=\"{$dark}\"/>
                <path d=\"M236 250 Q300 270 364 250 L364 330 Q300 350 236 330 Z\" fill=\"none\" stroke=\"{$line}\" stroke-width=\"2\" stroke-dasharray=\"6 5\"/>
                <path d=\"{$body}\" fill=\"url(#sh)\"/>
            </g>";
        } elseif ($view === 'folded') {
            $rect = '<rect x="110" y="170" width="380" height="470" rx="22"';
            $inner = "<g filter=\"url(#drop)\">
                {$rect} fill=\"{$base}\"/>" . ($fill ? "{$rect} fill=\"{$fill}\"/>" : '') . "
                {$rect} fill=\"url(#sh)\"/>
                <path d=\"M110 330 L490 330\" stroke=\"{$line}\" stroke-width=\"3\"/>
                <path d=\"M110 480 L490 480\" stroke=\"{$line}\" stroke-width=\"3\"/>
                <path d=\"M232 150 L300 232 L368 150 L344 122 Q300 140 256 122 Z\" fill=\"{$dark}\"/>
                <path d=\"M256 122 L300 232 L246 190 Z\" fill=\"{$darker}\" opacity=\".6\"/>
                <rect x=\"292\" y=\"232\" width=\"16\" height=\"408\" fill=\"{$dark}\" opacity=\".5\"/>
                <circle cx=\"300\" cy=\"270\" r=\"7\" fill=\"{$btn}\" stroke=\"{$dark}\"/><circle cx=\"300\" cy=\"380\" r=\"7\" fill=\"{$btn}\" stroke=\"{$dark}\"/><circle cx=\"300\" cy=\"530\" r=\"7\" fill=\"{$btn}\" stroke=\"{$dark}\"/>
            </g>";
        } else {
            $inner = "<g filter=\"url(#drop)\">
                <path d=\"{$sleeveL}\" fill=\"{$base}\"/><path d=\"{$sleeveR}\" fill=\"{$base}\"/>{$cuffs}
                <path d=\"{$body}\" fill=\"{$base}\"/>{$fabric}
                <path d=\"M196 128 L178 296\" stroke=\"{$line}\" stroke-width=\"2\" fill=\"none\"/>
                <rect x=\"288\" y=\"176\" width=\"24\" height=\"524\" fill=\"{$dark}\" opacity=\".35\"/>
                <rect x=\"336\" y=\"270\" width=\"64\" height=\"74\" rx=\"3\" fill=\"none\" stroke=\"{$line}\" stroke-width=\"2\"/>
                <path d=\"M336 270 L400 270 L400 330 L368 346 L336 330 Z\" fill=\"{$dark}\" opacity=\".25\"/>
                {$buttons}
                <path d=\"M300 176 L254 108 L212 134 L262 212 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>
                <path d=\"M300 176 L346 108 L388 134 L338 212 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>
                <path d=\"M254 108 Q300 128 346 108 L346 118 Q300 140 254 118 Z\" fill=\"{$dark}\"/>
                <path d=\"{$body}\" fill=\"url(#sh)\"/>
            </g>";
        }

        return $this->wrap($vb, $defs, $bg, $inner);
    }

    /* ------------------------------------------------------------ other garments */

    private function garment(string $hex, string $view, string $kind, string $pattern): string
    {
        $base = '#' . $hex;
        $dark = $this->shade($hex, -0.18);
        $darker = $this->shade($hex, -0.34);
        $light = $this->shade($hex, 0.16);
        $lum = hexdec(substr($hex, 0, 2)) * 0.299 + hexdec(substr($hex, 2, 2)) * 0.587 + hexdec(substr($hex, 4, 2)) * 0.114;
        $btn = $lum > 150 ? '#8a8a85' : '#e9e6df';
        $line = $lum > 150 ? 'rgba(0,0,0,.2)' : 'rgba(255,255,255,.24)';
        $thread = $kind === 'jeans' ? '#e8a44a' : $line;

        $patDef = '';
        $fill = '';
        if ($def = $this->patternDef($pattern)) {
            $patDef = sprintf($def, $lum > 150 ? '#000' : '#fff', $lum > 150 ? '.16' : '.22');
            $fill = 'url(#pat)';
        }

        $bottom = in_array($kind, ['trouser', 'chino', 'jeans', 'jogger'], true);
        $vb = $view === 'detail' ? ($bottom ? '150 130 300 300' : '150 60 300 380') : '0 0 600 760';
        [$vx, $vy, $vw, $vh] = array_map('floatval', explode(' ', $vb));

        $defs = '<defs>
            <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fbf4e6"/><stop offset="1" stop-color="#ecdfc6"/></linearGradient>
            <linearGradient id="sh" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#000" stop-opacity=".18"/><stop offset=".35" stop-color="#000" stop-opacity="0"/><stop offset=".7" stop-color="#fff" stop-opacity=".09"/><stop offset="1" stop-color="#000" stop-opacity=".2"/></linearGradient>
            <filter id="drop" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="0" dy="14" stdDeviation="14" flood-color="#000" flood-opacity=".18"/></filter>'
            . $patDef . '</defs>';
        $bg = "<rect x=\"{$vx}\" y=\"{$vy}\" width=\"{$vw}\" height=\"{$vh}\" fill=\"url(#bg)\"/>";

        $ov = fn (string ...$d) => $fill ? implode('', array_map(fn ($x) => "<path d=\"{$x}\" fill=\"{$fill}\"/>", $d)) : '';
        $mirror = fn (string $inner) => "<g transform=\"translate(600 0) scale(-1 1)\">{$inner}</g>";
        $dot = fn (float $x, float $y, float $r = 6) => "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"{$r}\" fill=\"{$btn}\" stroke=\"{$dark}\" stroke-width=\"1\"/>";
        $back = $view === 'back';

        if ($view === 'folded') {
            $rect = '<rect x="120" y="190" width="360" height="430" rx="22"';
            $mark = $bottom
                ? "<rect x=\"120\" y=\"190\" width=\"360\" height=\"46\" rx=\"14\" fill=\"{$dark}\"/>" . $dot(300, 213, 8)
                  . "<path d=\"M300 236 L300 520\" stroke=\"{$thread}\" stroke-width=\"3\"/><path d=\"M150 290 Q205 360 260 290\" stroke=\"{$thread}\" fill=\"none\" stroke-width=\"3\"/>"
                : "<path d=\"M232 190 L300 270 L368 190 Q300 222 232 190 Z\" fill=\"{$dark}\"/><path d=\"M232 190 Q300 300 368 190\" stroke=\"{$darker}\" fill=\"none\" stroke-width=\"3\"/>";
            $inner = "<g filter=\"url(#drop)\">{$rect} fill=\"{$base}\"/>" . ($fill ? "{$rect} fill=\"{$fill}\"/>" : '')
                . "{$rect} fill=\"url(#sh)\"/><path d=\"M120 340 L480 340\" stroke=\"{$line}\" stroke-width=\"3\"/><path d=\"M120 480 L480 480\" stroke=\"{$line}\" stroke-width=\"3\"/>{$mark}</g>";

            return $this->wrap($vb, $defs, $bg, $inner);
        }

        // ---------- bottoms
        if ($bottom) {
            $jog = $kind === 'jogger';
            $legs = $jog
                ? 'M190 172 L410 172 L406 690 L322 700 L300 340 L278 700 L194 690 Z'
                : 'M190 172 L410 172 L444 706 L322 706 L300 340 L278 706 L156 706 Z';
            $wb = "<rect x=\"190\" y=\"150\" width=\"220\" height=\"34\" rx=\"5\" fill=\"{$dark}\"/>";
            $loops = '';
            if (!$jog) {
                foreach ([204, 246, 342, 384] as $x) {
                    $loops .= "<rect x=\"{$x}\" y=\"146\" width=\"10\" height=\"42\" rx=\"2\" fill=\"{$darker}\" opacity=\".7\"/>";
                }
            }
            $cuffs = $jog
                ? "<rect x=\"194\" y=\"676\" width=\"128\" height=\"26\" rx=\"6\" fill=\"{$darker}\"/><rect x=\"278\" y=\"676\" width=\"124\" height=\"26\" rx=\"6\" fill=\"{$darker}\"/>"
                : '';

            if ($back) {
                $details = "<path d=\"M300 184 L300 340\" stroke=\"{$thread}\" stroke-width=\"2.5\"/>"
                    . "<path d=\"M214 230 L284 230 L278 306 L249 322 L220 306 Z\" fill=\"none\" stroke=\"{$thread}\" stroke-width=\"2.5\" stroke-dasharray=\"" . ($kind === 'jeans' ? '6 4' : '0') . "\"/>"
                    . "<path d=\"M316 230 L386 230 L380 306 L351 322 L322 306 Z\" fill=\"none\" stroke=\"{$thread}\" stroke-width=\"2.5\" stroke-dasharray=\"" . ($kind === 'jeans' ? '6 4' : '0') . "\"/>"
                    . ($kind === 'jeans' ? "<path d=\"M214 248 Q249 276 284 248\" stroke=\"{$thread}\" fill=\"none\" stroke-width=\"2\"/><path d=\"M316 248 Q351 276 386 248\" stroke=\"{$thread}\" fill=\"none\" stroke-width=\"2\"/>" : '');
            } else {
                $dash = $kind === 'jeans' ? ' stroke-dasharray="6 4"' : '';
                $details = "<path d=\"M190 184 Q232 250 276 214\" fill=\"none\" stroke=\"{$thread}\" stroke-width=\"2.5\"{$dash}/>"
                    . "<path d=\"M410 184 Q368 250 324 214\" fill=\"none\" stroke=\"{$thread}\" stroke-width=\"2.5\"{$dash}/>"
                    . "<path d=\"M300 184 L300 330 Q312 336 322 322\" fill=\"none\" stroke=\"{$thread}\" stroke-width=\"2.5\"{$dash}/>"
                    . $dot(300, 167, 7);
                if ($kind === 'trouser' || $kind === 'chino') {
                    $details .= "<path d=\"M236 190 L222 706\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M364 190 L378 706\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                }
                if ($jog) {
                    $details .= "<path d=\"M290 184 Q270 240 276 300\" stroke=\"{$light}\" stroke-width=\"4\" fill=\"none\"/><path d=\"M310 184 Q330 240 324 300\" stroke=\"{$light}\" stroke-width=\"4\" fill=\"none\"/>";
                }
            }
            $inner = "<g filter=\"url(#drop)\"><path d=\"{$legs}\" fill=\"{$base}\"/>" . ($kind === 'jeans' ? '' : $ov($legs))
                . ($jog ? '' : $wb . $loops) . ($jog ? "<rect x=\"190\" y=\"166\" width=\"220\" height=\"26\" rx=\"6\" fill=\"{$darker}\"/>" : '')
                . $details . $cuffs . "<path d=\"{$legs}\" fill=\"url(#sh)\"/></g>";

            return $this->wrap($vb, $defs, $bg, $inner);
        }

        // ---------- tops, outerwear, dresses
        $long = !in_array($kind, ['tee', 'polo', 'dress'], true);
        $sleeveL = $long
            ? 'M196 150 L106 182 L60 610 L138 626 L190 330 Z'
            : 'M200 142 L110 178 L82 300 L172 322 L196 240 Z';
        $bodies = [
            'tee' => 'M200 140 Q300 190 400 140 L432 626 Q300 646 168 626 Z',
            'polo' => 'M200 140 Q300 180 400 140 L432 640 Q300 660 168 640 Z',
            'hoodie' => 'M192 172 Q300 204 408 172 L440 690 Q300 712 160 690 Z',
            'sweat' => 'M196 150 Q300 190 404 150 L434 690 Q300 712 166 690 Z',
            'jacket' => 'M192 156 Q300 184 408 156 L432 650 Q300 668 168 650 Z',
            'blazer' => 'M190 140 Q300 168 410 140 L446 720 Q300 744 154 720 Z',
            'kurta' => 'M200 140 Q300 188 400 140 L432 520 L458 706 Q300 726 142 706 L168 520 Z',
            'dress' => 'M232 128 Q300 176 368 128 L384 290 L476 704 Q300 732 124 704 L216 290 Z',
        ];
        $body = $bodies[$kind];
        $shapes = "<path d=\"{$body}\" fill=\"{$base}\"/>";
        $armFill = '';
        if ($kind !== 'dress') {
            $armFill = "<path d=\"{$sleeveL}\" fill=\"{$base}\"/>" . $mirror("<path d=\"{$sleeveL}\" fill=\"{$base}\"/>");
        }
        $fabric = $ov($body) . ($kind !== 'dress' ? $ov($sleeveL) . $mirror($ov($sleeveL)) : '');
        $trim = '';
        $extra = '';

        switch ($kind) {
            case 'tee':
            case 'sweat':
                $trim = "<path d=\"M244 140 Q300 220 356 140 L344 134 Q300 190 256 134 Z\" fill=\"{$dark}\"/>";
                if ($kind === 'sweat') {
                    $trim .= "<rect x=\"166\" y=\"664\" width=\"268\" height=\"30\" rx=\"8\" fill=\"{$dark}\"/>"
                        . "<rect x=\"62\" y=\"596\" width=\"78\" height=\"30\" rx=\"8\" transform=\"rotate(10 100 610)\" fill=\"{$dark}\"/>"
                        . $mirror("<rect x=\"62\" y=\"596\" width=\"78\" height=\"30\" rx=\"8\" transform=\"rotate(10 100 610)\" fill=\"{$dark}\"/>");
                } else {
                    $trim .= "<path d=\"M168 626 Q300 646 432 626\" stroke=\"{$line}\" stroke-width=\"3\" fill=\"none\"/>";
                }
                if (!$back) {
                    $extra = "<path d=\"M200 140 L196 240\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                } else {
                    $trim = "<path d=\"M250 138 Q300 156 350 138 L346 150 Q300 168 254 150 Z\" fill=\"{$dark}\"/>" . ($kind === 'sweat' ? substr($trim, strpos($trim, '<rect')) : '');
                }
                break;
            case 'polo':
                $trim = "<path d=\"M300 190 L252 112 L210 138 L262 218 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>"
                    . "<path d=\"M300 190 L348 112 L390 138 L338 218 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>"
                    . "<path d=\"M252 112 Q300 132 348 112 L348 122 Q300 146 252 122 Z\" fill=\"{$dark}\"/>";
                if (!$back) {
                    $extra = "<rect x=\"290\" y=\"190\" width=\"20\" height=\"120\" fill=\"{$dark}\" opacity=\".4\"/>" . $dot(300, 220) . $dot(300, 258) . $dot(300, 296)
                        . "<path d=\"M172 322 L196 240\" stroke=\"{$line}\" stroke-width=\"2\"/><rect x=\"82\" y=\"290\" width=\"92\" height=\"18\" fill=\"{$dark}\" opacity=\".5\" transform=\"rotate(-8 128 300)\"/>";
                }
                break;
            case 'hoodie':
                $hoodBack = "<path d=\"M214 160 Q206 56 300 46 Q394 56 386 160 Q300 206 214 160 Z\" fill=\"{$dark}\"/>";
                $trim = ($back ? "<path d=\"M226 150 Q300 214 374 150 Q372 80 300 70 Q228 80 226 150 Z\" fill=\"{$dark}\"/>"
                        : "<path d=\"M236 158 Q300 226 364 158 Q354 98 300 90 Q246 98 236 158 Z\" fill=\"{$darker}\"/>"
                          . "<path d=\"M262 190 L258 290\" stroke=\"{$btn}\" stroke-width=\"4\" stroke-linecap=\"round\"/><path d=\"M338 190 L342 290\" stroke=\"{$btn}\" stroke-width=\"4\" stroke-linecap=\"round\"/>"
                          . "<circle cx=\"258\" cy=\"294\" r=\"5\" fill=\"{$btn}\"/><circle cx=\"342\" cy=\"294\" r=\"5\" fill=\"{$btn}\"/>")
                    . "<rect x=\"158\" y=\"660\" width=\"284\" height=\"36\" rx=\"8\" fill=\"{$dark}\"/>"
                    . "<rect x=\"56\" y=\"590\" width=\"84\" height=\"34\" rx=\"8\" transform=\"rotate(10 98 606)\" fill=\"{$dark}\"/>"
                    . $mirror("<rect x=\"56\" y=\"590\" width=\"84\" height=\"34\" rx=\"8\" transform=\"rotate(10 98 606)\" fill=\"{$dark}\"/>");
                if (!$back) {
                    $extra = "<path d=\"M216 470 L384 470 L408 640 L192 640 Z\" fill=\"{$dark}\" opacity=\".55\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M226 480 L214 630\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M374 480 L386 630\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                }
                $shapes = $hoodBack . $shapes;
                break;
            case 'jacket':
                $trim = "<rect x=\"158\" y=\"622\" width=\"284\" height=\"40\" rx=\"8\" fill=\"{$dark}\"/><rect x=\"56\" y=\"592\" width=\"84\" height=\"36\" rx=\"8\" transform=\"rotate(10 98 610)\" fill=\"{$dark}\"/>"
                    . $mirror("<rect x=\"56\" y=\"592\" width=\"84\" height=\"36\" rx=\"8\" transform=\"rotate(10 98 610)\" fill=\"{$dark}\"/>")
                    . "<path d=\"M252 124 Q300 148 348 124 L350 160 Q300 190 250 160 Z\" fill=\"{$dark}\"/>";
                if (!$back) {
                    $extra = "<path d=\"M300 176 L300 624\" stroke=\"{$btn}\" stroke-width=\"4\" stroke-dasharray=\"4 3\"/><rect x=\"294\" y=\"170\" width=\"12\" height=\"26\" rx=\"3\" fill=\"{$btn}\"/>"
                        . "<path d=\"M214 430 L262 430 L256 500 L206 500 Z\" fill=\"none\" stroke=\"{$line}\" stroke-width=\"2.5\"/><path d=\"M386 430 L338 430 L344 500 L394 500 Z\" fill=\"none\" stroke=\"{$line}\" stroke-width=\"2.5\"/>";
                } else {
                    $extra = "<path d=\"M300 176 L300 622\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                }
                break;
            case 'blazer':
                if (!$back) {
                    $trim = "<path d=\"M256 140 L300 420 L344 140 Q300 168 256 140 Z\" fill=\"#f4efe6\"/>"
                        . "<path d=\"M256 150 L300 330 L286 142 Z\" fill=\"#d9d2c4\"/>"
                        . "<path d=\"M262 138 L196 150 L226 270 L300 420 L284 250 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>"
                        . "<path d=\"M338 138 L404 150 L374 270 L300 420 L316 250 Z\" fill=\"{$light}\" stroke=\"{$dark}\" stroke-width=\"1.5\"/>"
                        . $dot(300, 440, 8) . $dot(300, 520, 8)
                        . "<path d=\"M212 560 L276 560 L272 594 L208 594 Z\" fill=\"{$dark}\" opacity=\".5\"/><path d=\"M388 560 L324 560 L328 594 L392 594 Z\" fill=\"{$dark}\" opacity=\".5\"/>"
                        . "<path d=\"M222 330 L256 330\" stroke=\"{$line}\" stroke-width=\"3\"/>"
                        . "<path d=\"M44 600 L128 616\" stroke=\"{$dark}\" stroke-width=\"6\"/>";
                } else {
                    $trim = "<path d=\"M252 130 Q300 152 348 130 L352 158 Q300 182 248 158 Z\" fill=\"{$dark}\"/><path d=\"M300 182 L300 720\" stroke=\"{$line}\" stroke-width=\"2.5\"/><path d=\"M300 520 L268 720 M300 520 L332 720\" stroke=\"{$line}\" stroke-width=\"2\" fill=\"none\"/>";
                }
                break;
            case 'kurta':
                $trim = "<path d=\"M262 134 Q300 160 338 134 L336 168 Q300 190 264 168 Z\" fill=\"{$dark}\"/>"
                    . "<path d=\"M168 520 L176 706\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M432 520 L424 706\" stroke=\"{$line}\" stroke-width=\"2\"/>"
                    . "<path d=\"M142 706 Q300 726 458 706\" stroke=\"{$dark}\" stroke-width=\"8\" fill=\"none\"/>"
                    . "<path d=\"M58 600 L138 618\" stroke=\"{$dark}\" stroke-width=\"8\"/>" . $mirror("<path d=\"M58 600 L138 618\" stroke=\"{$dark}\" stroke-width=\"8\"/>");
                if (!$back) {
                    $extra = "<rect x=\"291\" y=\"170\" width=\"18\" height=\"230\" fill=\"{$dark}\" opacity=\".4\"/>" . $dot(300, 214, 5) . $dot(300, 262, 5) . $dot(300, 310, 5)
                        . "<path d=\"M213 400 L190 470\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M387 400 L410 470\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                }
                break;
            case 'dress':
                $trim = "<path d=\"M232 128 Q300 200 368 128 L360 124 Q300 176 240 124 Z\" fill=\"{$dark}\"/>"
                    . "<rect x=\"214\" y=\"282\" width=\"172\" height=\"20\" rx=\"4\" fill=\"{$dark}\"/>"
                    . "<path d=\"M232 128 L222 84 M368 128 L378 84\" stroke=\"{$base}\" stroke-width=\"14\" stroke-linecap=\"round\"/>"
                    . "<path d=\"M124 704 Q300 732 476 704\" stroke=\"{$dark}\" stroke-width=\"6\" fill=\"none\"/>";
                $extra = "<path d=\"M300 302 L262 704\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M300 302 L340 704\" stroke=\"{$line}\" stroke-width=\"2\"/><path d=\"M300 302 L300 704\" stroke=\"{$line}\" stroke-width=\"2\"/>";
                break;
        }

        $inner = "<g filter=\"url(#drop)\">{$armFill}{$shapes}{$fabric}{$trim}{$extra}<path d=\"{$body}\" fill=\"url(#sh)\"/></g>";

        return $this->wrap($vb, $defs, $bg, $inner);
    }

    private function wrap(string $viewBox, string $defs, string $bg, string $inner): string
    {
        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"{$viewBox}\" width=\"600\" height=\"760\" preserveAspectRatio=\"xMidYMid slice\">{$defs}{$bg}{$inner}</svg>";
    }
}
