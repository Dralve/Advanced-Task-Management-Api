<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        //X-Powered-By, Server, and x-turbo-charged-by:
        // These headers often reveal unnecessary information about the server environment or the software running on the server (e.g., the framework or server version).
        // Removing these headers improves security by minimizing fingerprinting risks, where attackers gather information about your system.
        $request->headers->remove('X-Powered-By');
        $request->headers->remove('Server');
        $request->headers->remove('x-turbo-charged-by');

        //X-Frame-Options: deny: Prevents the website from being embedded in iframes (to protect against click jacking attacks).
        $response->headers->set('X-Frame-Options','deny');

        //X-Content-Type-Options: nosniff: Instructs the browser not to try and guess (or "sniff") the MIME type of files,
        // ensuring they are interpreted as their true type. This helps prevent certain types of attacks (like Cross-Site Scripting - XSS).
        $response->headers->set('X-Content-Type-Options','nosniff');

        //X-Permitted-Cross-Domain-Policies: none: Prevents other domains from embedding content or sharing data, enhancing privacy and security.
        $response->headers->set('X-Permitted-Cross-Domain-Policies','none');

        //Referrer-Policy: no-referrer: Prevents the browser from sending referrer information in headers, protecting user privacy by not revealing the previous page visited.
        $response->headers->set('Referrer-Policy','no-referrer');

        //Cross-Origin-Embedder-Policy: require-corp: Ensures that resources embedded in your site are only accessible if they come from the same origin or trusted sources,
        // improving cross-origin resource protection.
        $response->headers->set('Referrer-Policy','no-referrer');

       //Cross-Origin-Embedder-Policy: require-corp: Ensures that resources embedded in your site are only accessible if they come from the same origin or trusted sources,
        // improving cross-origin resource protection.
        $response->headers->set('Cross-Origin-Embedder-Policy','require-corp');

        //Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self': This defines a strict content security policy, which restricts where resources (scripts, styles, etc.) can be loaded from:
        //default-src 'none': Blocks all resources unless explicitly allowed.
        //style-src 'self': Only allows stylesheets from the same origin.
        //form-action 'self': Only allows forms to be submitted to the same origin
        $response->headers->set('Content-Security-Policy',"default-src 'none'; style-src 'self'; form-action 'self'");

        //X-XSS-Protection: 1; mode=block: Enables the browser's built-in XSS (Cross-Site Scripting) protection and instructs it to block the page if an attack is detected.
        $response->headers->set('X-XSS-Protection','1; mode=block');

        //Protects against misused or fraudulent SSL certificates by requiring that your website’s certificate be logged in public Certificate Transparency (CT) logs.
        //max-age=86400: How long (in seconds) the policy should be cached.
        //enforce: Forces the browser to reject non-CT-compliant certificates.
        $response->headers->set('Expect-CT', 'max-age=86400, enforce');

        //Controls which browser features (like geolocation, camera, microphone) can be used on your site, reducing the risk of abuse.
        $response->headers->set('Permissions-Policy', "geolocation=(), microphone=(), camera=(), fullscreen=(self)");

        //Helps manage client-side caching and reduces the risk of exposing sensitive data through the browser’s cache.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        //Ensures that user data is cleared when they log out or when you need to enforce a full data reset.
        $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage", "executionContexts"');


        //To inform clients about the rate limits imposed on the API, which helps to avoid abuse or Denial-of-Service (DoS) attacks.
        $response->headers->set('X-RateLimit-Limit', '1000');
        $response->headers->set('X-RateLimit-Remaining', '950');
        $response->headers->set('X-RateLimit-Reset', '3600');


        //Prevents Internet Explorer from allowing users to open files directly (without prompting them to download), which can reduce the risk of exposing sensitive content.
        $response->headers->set('X-Download-Options', 'noopen');


        //Strict-Transport-Security: max-age=31536000; includeSubDomains: Instructs the browser to only communicate with the server over HTTPS (for 1 year, as 31536000 seconds) and applies the rule to all subdomains as well.
        // This prevents downgrade attacks where an attacker could try to redirect users to an unsecured (HTTP) version of the site.
        if (config('app.env') === 'production'){
            $response->headers->set('Strict-Transport-Security','max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
