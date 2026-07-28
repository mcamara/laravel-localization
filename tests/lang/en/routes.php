<?php

return [
    'about'               => 'about',
    'view'                => 'view/{id}',
    'view_project'        => 'view/{id}/project/{project_id?}',
    'view_with_slug'      => 'view/{post:slug}',
    'view_category_post'  => 'view/{category:slug}/{post:slug}',
    'view_post_comment'   => 'view/{post:slug}/{comment:slug?}',
    'manage'              => 'manage/{file_id?}',
    'hello'               => 'Hello world',
    'test_text'           => 'Test text',
];
