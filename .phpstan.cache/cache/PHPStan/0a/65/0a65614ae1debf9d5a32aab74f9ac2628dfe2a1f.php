<?php declare(strict_types = 1);

// odsl-E:\UTP Scholarship system\src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    'E:\\UTP Scholarship system\\src\\Contracts\\AuthenticatesUsers.php' => 
    array (
      0 => '0d020e75c3099d9c8a250a0cafefd0db46dccdc3',
      1 => 
      array (
        0 => 'utp\\contracts\\authenticatesusers',
      ),
      2 => 
      array (
        0 => 'utp\\contracts\\registeruser',
        1 => 'utp\\contracts\\loginuser',
        2 => 'utp\\contracts\\getcurrentuser',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Contracts\\ChecksEligibility.php' => 
    array (
      0 => '184ea09ecfb56808dfa3d75a13e9e6a142e0909b',
      1 => 
      array (
        0 => 'utp\\contracts\\checkseligibility',
      ),
      2 => 
      array (
        0 => 'utp\\contracts\\checkeligibility',
        1 => 'utp\\contracts\\getmatchingscholarships',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Core\\SessionManager.php' => 
    array (
      0 => '881f958032352d98c43d6aea6b548fe9824d9566',
      1 => 
      array (
        0 => 'utp\\core\\sessionmanager',
      ),
      2 => 
      array (
        0 => 'utp\\core\\__construct',
        1 => 'utp\\core\\start',
        2 => 'utp\\core\\logout',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\CSRF.php' => 
    array (
      0 => '57d14f6e5e0b87bbf75efd279c90f85f581042b4',
      1 => 
      array (
        0 => 'utp\\security\\csrf',
      ),
      2 => 
      array (
        0 => 'utp\\security\\generatetoken',
        1 => 'utp\\security\\validatetoken',
        2 => 'utp\\security\\field',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\InputSanitizer.php' => 
    array (
      0 => '32660f83dd3c5b147b25696fb7338e9874d3b390',
      1 => 
      array (
        0 => 'utp\\security\\inputsanitizer',
      ),
      2 => 
      array (
        0 => 'utp\\security\\sanitize',
        1 => 'utp\\security\\escape',
        2 => 'utp\\security\\validateemail',
        3 => 'utp\\security\\validatepassword',
        4 => 'utp\\security\\validateicnumber',
        5 => 'utp\\security\\validatephone',
        6 => 'utp\\security\\setsecurityheaders',
        7 => 'utp\\security\\getclientip',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\PasswordStrength.php' => 
    array (
      0 => '224620072eef83bff8c3446633fdf17c654810bf',
      1 => 
      array (
        0 => 'utp\\security\\passwordstrength',
      ),
      2 => 
      array (
        0 => 'utp\\security\\evaluate',
        1 => 'utp\\security\\validate',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\RateLimiter.php' => 
    array (
      0 => '7a20b5919bf0a9848c7a5185dab4f12e29f24b14',
      1 => 
      array (
        0 => 'utp\\security\\ratelimiter',
      ),
      2 => 
      array (
        0 => 'utp\\security\\__construct',
        1 => 'utp\\security\\check',
        2 => 'utp\\security\\record',
        3 => 'utp\\security\\clear',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\RoleGuard.php' => 
    array (
      0 => '88c0ba5d31cf8a18e23befb3d04f5e105ed02e43',
      1 => 
      array (
        0 => 'utp\\security\\roleguard',
      ),
      2 => 
      array (
        0 => 'utp\\security\\__construct',
        1 => 'utp\\security\\isloggedin',
        2 => 'utp\\security\\isadmin',
        3 => 'utp\\security\\isstudent',
        4 => 'utp\\security\\reverifyrole',
        5 => 'utp\\security\\requirelogin',
        6 => 'utp\\security\\requireadmin',
        7 => 'utp\\security\\requirestudent',
        8 => 'utp\\security\\isverified',
        9 => 'utp\\security\\requireverified',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Security\\TwoFactorAuth.php' => 
    array (
      0 => '14abe39d1ab215dadf51f659abe67ab854cc6d50',
      1 => 
      array (
        0 => 'utp\\security\\twofactorauth',
      ),
      2 => 
      array (
        0 => 'utp\\security\\__construct',
        1 => 'utp\\security\\generatesecret',
        2 => 'utp\\security\\verifycode',
        3 => 'utp\\security\\isenabled',
        4 => 'utp\\security\\disable',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\AIEngine.php' => 
    array (
      0 => 'd501fe2006b26bb3dda59e0d35a60e28f1c7f425',
      1 => 
      array (
        0 => 'utp\\services\\aiengine',
      ),
      2 => 
      array (
        0 => 'utp\\services\\__construct',
        1 => 'utp\\services\\checkeligibility',
        2 => 'utp\\services\\evaluateprogramme',
        3 => 'utp\\services\\generaterecommendation',
        4 => 'utp\\services\\getmatchingscholarships',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\AuditLogger.php' => 
    array (
      0 => '6700792a51ecc15caaaea311f46b9390e7d690a2',
      1 => 
      array (
        0 => 'utp\\services\\auditlogger',
      ),
      2 => 
      array (
        0 => 'utp\\services\\__construct',
        1 => 'utp\\services\\log',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\GradeMapper.php' => 
    array (
      0 => '4042ae047118515bd93eb676bb7b8c5a7690e5de',
      1 => 
      array (
        0 => 'utp\\services\\grademapper',
      ),
      2 => 
      array (
        0 => 'utp\\services\\gradetopoints',
        1 => 'utp\\services\\getminpasspoints',
        2 => 'utp\\services\\getmaxpoints',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\Mailer.php' => 
    array (
      0 => 'd683f809e9fa682a590a7ed3848676656266adad',
      1 => 
      array (
        0 => 'utp\\services\\mailer',
      ),
      2 => 
      array (
        0 => 'utp\\services\\createmailer',
        1 => 'utp\\services\\sendverificationemail',
        2 => 'utp\\services\\sendapplicationstatusemail',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\Telemetry.php' => 
    array (
      0 => 'f98cc822b8aab975a4da24b9dc7b2eebd521d437',
      1 => 
      array (
        0 => 'utp\\services\\telemetry',
      ),
      2 => 
      array (
        0 => 'utp\\services\\init',
        1 => 'utp\\services\\trackevent',
        2 => 'utp\\services\\starttimer',
        3 => 'utp\\services\\endtimer',
      ),
      3 => 
      array (
      ),
    ),
    'E:\\UTP Scholarship system\\src\\Services\\UserAuth.php' => 
    array (
      0 => 'c5cd29c14370bba1f74335da1fcc9d557336b4aa',
      1 => 
      array (
        0 => 'utp\\services\\userauth',
      ),
      2 => 
      array (
        0 => 'utp\\services\\__construct',
        1 => 'utp\\services\\registeruser',
        2 => 'utp\\services\\loginuser',
        3 => 'utp\\services\\getcurrentuser',
      ),
      3 => 
      array (
      ),
    ),
  ),
));