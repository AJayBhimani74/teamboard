@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="card narrow">
    <h1>Create an account</h1>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label>Name <input name="name" value="{{ old('name') }}" required></label>
        @error('name') <p class="error">{{ $message }}</p> @enderror
        <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
        @error('email') <p class="error">{{ $message }}</p> @enderror
        <label>Password <input type="password" name="password" required></label>
        @error('password') <p class="error">{{ $message }}</p> @enderror
        <label>Confirm password <input type="password" name="password_confirmation" required></label>
        <button class="btn" type="submit">Register</button>
    </form>
</div>
@endsection
