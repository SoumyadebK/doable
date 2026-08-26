<?php

/** Shared site footer + closing tags. Expects $content/$base from header.php. */
$content = $content ?? get_content();
$base    = $base ?? base_path();
[$enrollUrl, $enrollExternal] = enroll_link();
$year = date('Y');
?>
</main>

<footer class="bg-gray-900 text-gray-300">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
      <div class="max-w-md">
        <img src="v2/assets/images/doable-logo-white.png" alt="<?= e(SITE_NAME) ?> logo" class="h-10 w-auto mb-4">
        <p class="text-gray-400 leading-relaxed"><?= e($content['general']['footerTagline']) ?></p>
      </div>
      <div class="md:justify-self-end">
        <h4 class="text-white font-semibold mb-4">Quick Links</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="<?= $base ?>/index.php#features" class="hover:text-emerald-400 transition-colors">Features</a></li>
          <li><a href="<?= $base ?>/index.php#industries" class="hover:text-emerald-400 transition-colors">Industries</a></li>
          <li><a href="<?= $base ?>/index.php#pricing" class="hover:text-emerald-400 transition-colors">Pricing</a></li>
          <li><a href="<?= $base ?>/blog.php" class="hover:text-emerald-400 transition-colors">Blog</a></li>
          <li><a href="<?= e($enrollUrl) ?>" <?= $enrollExternal ? ' target="_blank" rel="noopener"' : '' ?> class="hover:text-emerald-400 transition-colors">Enroll</a></li>
          <li><a href="privacy_policy.php" class="hover:text-emerald-400 transition-colors">Privacy Policy</a></li>
          <li><a href="terms_of_use.php" class="hover:text-emerald-400 transition-colors">Terms of Use</a></li>
        </ul>
      </div>
    </div>
    <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
      <p>&copy; <?= $year ?> <?= e($content['general']['companyName']) ?>. All rights reserved.</p>
      <p>Made with <span class="text-emerald-400">&#9829;</span> for business owners.</p>
    </div>
  </div>
</footer>

<script src="v2/assets/js/main.js"></script>
</body>

</html>