// Temporary recovery entrypoint: load the preserved built app bundle
// from public assets so Vite dev/build can run until source is restored.
const recoveredCssHref = '/recovered/app.css';
const recoveredJsSrc = '/recovered/app.js';

if (!document.querySelector(`link[href="${recoveredCssHref}"]`)) {
  const stylesheet = document.createElement('link');
  stylesheet.rel = 'stylesheet';
  stylesheet.href = recoveredCssHref;
  document.head.appendChild(stylesheet);
}

if (!document.querySelector(`script[src="${recoveredJsSrc}"]`)) {
  const script = document.createElement('script');
  script.type = 'module';
  script.src = recoveredJsSrc;
  document.body.appendChild(script);
}
