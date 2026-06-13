<?php
session_unset();
session_destroy();
hmn_json_response(['success' => true]);
