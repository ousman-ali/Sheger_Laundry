<tr>
    <td class="header" style="padding: 25px 0; text-align: center;">
        @php
            use Illuminate\Support\Str;

            $companyLogo = \App\Models\SystemSetting::getValue('company_logo_url');
            $companyName = \App\Models\SystemSetting::getValue('company_name', config('app.name', 'Sheger Automatic Laundry'));
            $appUrl = config('app.url');
            $preferHttps = Str::startsWith((string)$appUrl, 'https://');

            $embeddedLogo = null;
            $logoUrl = null;

            if (!empty($companyLogo)) {
                $isAbsolute = Str::startsWith($companyLogo, ['http://', 'https://']);

                // Attempt to embed from a local file when the URL points to our own host
                if ($isAbsolute) {
                    $logoUrl = $companyLogo;
                    try {
                        $appHost = parse_url((string) $appUrl, PHP_URL_HOST);
                        $logoHost = parse_url($companyLogo, PHP_URL_HOST);
                        $logoPath = parse_url($companyLogo, PHP_URL_PATH) ?: '';
                        if ($appHost && $logoHost && strcasecmp($appHost, $logoHost) === 0) {
                            $candidate = public_path(ltrim($logoPath, '/'));
                            if (isset($message) && $candidate && file_exists($candidate) && is_readable($candidate)) {
                                if (method_exists($message, 'embedFromPath')) {
                                    $embeddedLogo = $message->embedFromPath($candidate);
                                } else {
                                    $embeddedLogo = $message->embed($candidate);
                                }
                            }
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                } else {
                    $normalized = ltrim($companyLogo, '/');
                    // Try to embed from local file if it exists
                    $candidate = public_path($normalized);
                    if (isset($message) && $candidate && file_exists($candidate) && is_readable($candidate)) {
                        try {
                            if (method_exists($message, 'embedFromPath')) {
                                $embeddedLogo = $message->embedFromPath($candidate);
                            } else {
                                $embeddedLogo = $message->embed($candidate);
                            }
                        } catch (\Throwable $e) { /* ignore */ }
                    }
                    $logoUrl = secure_url($normalized);
                }
            } else {
                // Default to public/logo.png
                $candidate = public_path('logo.png');
                if (isset($message) && file_exists($candidate) && is_readable($candidate)) {
                    try {
                        if (method_exists($message, 'embedFromPath')) {
                            $embeddedLogo = $message->embedFromPath($candidate);
                        } else {
                            $embeddedLogo = $message->embed($candidate);
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }
                $logoUrl = secure_asset('logo.png');
            }

            // If app prefers HTTPS but logo is absolute HTTP, upgrade scheme to HTTPS
            if ($preferHttps && Str::startsWith($logoUrl, 'http://')) {
                $logoUrl = 'https://'.Str::after($logoUrl, 'http://');
            }

            $imgSrc = $embeddedLogo ?: $logoUrl;
        @endphp
        <a href="{{ config('app.url') }}" style="display:inline-block; text-decoration:none;">
            <img src="{{ $imgSrc }}" alt="{{ $companyName }} Logo" class="logo" style="height:48px; width:auto;">
        </a>
    </td>
 </tr>
