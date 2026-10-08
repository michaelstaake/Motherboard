<?php
$version = '26.100';
$channel = 'release';

// Releases before 26.100 used a four-segment scheme (e.g. 26.10.8.6). Code that still
// expects that shape gets this fixed placeholder, which sorts after every real
// four-segment release. It never changes.
$legacyVersion = '26.10.8.7';
