---
layout: homepage
---

<header>
    <div class="inner-content">
      <a href="http://thephpleague.com/" class="league">
          Presented by The League of Extraordinary Packages
      </a>
      <h1>{{ site.data.project.title }}</h1>
      <h2>{{ site.data.project.tagline }}</h2>
      <p class="composer"><span>$ composer require league/container</span></p>
    </div>
</header>

<main>
  <div class="example">
    <div class="inner-content">
      <h1>Usage</h1>

<div class="language-php highlighter-rouge"><pre class="highlight"><code><span class="cp">&lt;?php</span>

<span class="nv">$container</span> <span class="o">=</span> <span class="k">new</span> <span class="nx">League\Container\Container</span><span class="p">;</span>

<span class="c1">// add a service to the container
</span><span class="nv">$container</span><span class="o">-&gt;</span><span class="na">add</span><span class="p">(</span><span class="s1">'service'</span><span class="p">,</span> <span class="s1">'Acme\Service\SomeService'</span><span class="p">);</span>

<span class="c1">// retrieve the service from the container
</span><span class="nv">$service</span> <span class="o">=</span> <span class="nv">$container</span><span class="o">-&gt;</span><span class="na">get</span><span class="p">(</span><span class="s1">'service'</span><span class="p">);</span>

<span class="nb">var_dump</span><span class="p">(</span><span class="nv">$service</span> <span class="nx">instanceof</span> <span class="nx">Acme\Service\SomeService</span><span class="p">);</span> <span class="c1">// true
</span></code></pre>
</div>
    </div>
  </div>


  <div class="highlights">
    <div class="inner-content">
      <div class="column one">
        <h1>Highlights</h1>
        <div class="description">
        <p>Container is a simple but powerful dependency injection container that allows you to decouple components in your application in order to write clean and testable code.</p>
        <p>It is framework agnostic as well as being very fast because of it's simple API.</p>
        </div>
      </div>
      <div class="column two">
        <ol>
          <li><p>Simple API.</p></li>
          <li><p>Interoperabiity. Container is an implementation of PSR-11.</p></li>
          <li><p>Speed. Because Container is simple, it is also very fast.</p></li>
          <li><p>Service Providers allow you to package code or configuration for packages that you reuse regularly.</p></li>
          <li><p>Inflectors allow you to manipulate objects resolved through the container based on the type.</p></li>
        </ol>
      </div>
    </div>
  </div>

  <div class="documentation">
    <div class="inner-content">
      <h1>Releases</h1>

      <div class="version next">
                <h2>Next/master</h2>
                <div class="content">
                    <p><code>League\Container 3.x</code></p>
                    <ul>
                        <li>Requires: <strong>PHP >= 7.0.0</strong></li>
                        <li>Release Date: <strong>TBD</strong></li>
                        <li>Supported Until: <strong>TBD</strong></li>
                    </ul>
                    <p><a href="/3.x/">Full Documentation</a></p>
                </div>
            </div>

            <div class="version current">
                <h2>Current Stable Release</h2>
                <div class="content">
                    <p><code>League\Container 2.x</code></p>
                    <ul>
                        <li>Requires: <strong>PHP >= 5.4.0</strong></li>
                        <li>Latest Release: <strong>2.4.0 - 2017-03</strong></li>
                        <li>Supported Until: <strong>TBD</strong></li>
                    </ul>
                    <p><a href="/2.x/">Full Documentation</a></p>
                </div>
            </div>

            <div class="version legacy">
                <h2>No longer Supported</h2>
                <div class="content">
                    <p><code>League\Container 1.x</code></p>
                    <ul>
                        <li>Requires: <strong>PHP >= 5.4.0</strong></li>
                        <li>Final Release: <strong>1.3.2 - 2015-04</strong></li>
                        <li>Supported Until: <strong>2015-10</strong></li>
                    </ul>
                    <p><a href="/1.x/">Full Documentation</a></p>
                </div>
            </div>

      <p class="footnote">Once a new major version is released, the previous stable release remains supported for six more months through patches and security fixes.</p>

    </div>
  </div>

  <div class="questions">
    <div class="inner-content">
      <h1>Questions?</h1>
      <p><strong>League\Csv</strong> was created by Phil Bennett. Find him on Twitter at <a href="https://twitter.com/philipobenito">@philipobenito</a>.</p>
    </div>
  </div>
</main>