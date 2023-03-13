<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\QueryBuilders\ProfileQueryBuilder;

class AdviceController extends Controller
{
    public function index(ProfileQueryBuilder $profileQueryBuilder)
    {
        Auth::attempt(['email' => 'email@mail.com', 'password' => 'the-password-of-choice']); //чтобы польователь был зарегистрирован, когда появится регистрацию убрать
        // когда регистрация убрать



        if (Auth::check()) {
            // return \redirect()->route('home');
        }

        if (!$profileQueryBuilder->getByUserId(\Auth::id())) {

            return \redirect()->route('form');

        }
        $profile = $profileQueryBuilder->getByUserId(\Auth::id());
        return Inertia::render('Advice', [
            'profile' => $profile->toArray()[0],
        ]);
    }
}
