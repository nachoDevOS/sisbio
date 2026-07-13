<?php

test('la raíz redirige al panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin');
});
