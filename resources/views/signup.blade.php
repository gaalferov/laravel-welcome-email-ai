<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - {{ config('app.name') }}</title>
    <!-- Tailwind CSS via CDN for demo simplicity. For production, use a build step: https://tailwindcss.com/docs/installation -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Create your account</h1>
            <p class="mt-2 text-gray-600">We'll send you a personalized welcome email</p>

            @if(config('services.mailtrap.sandbox'))
                <span class="inline-block mt-2 px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800 rounded-full">
                    Sandbox mode - emails go to Mailtrap inbox
                </span>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-red-800 text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="signup-form" method="POST" action="{{ route('signup.submit') }}" autocomplete="off" class="bg-white shadow-sm rounded-xl p-8 space-y-5 border border-gray-200">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Work email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Your role</label>
                <select name="role" id="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select your role</option>
                    <option value="Developer" @selected(old('role') === 'Developer')>Developer</option>
                    <option value="Engineering Manager" @selected(old('role') === 'Engineering Manager')>Engineering Manager</option>
                    <option value="Product Manager" @selected(old('role') === 'Product Manager')>Product Manager</option>
                    <option value="DevOps / SRE" @selected(old('role') === 'DevOps / SRE')>DevOps / SRE</option>
                    <option value="QA Engineer" @selected(old('role') === 'QA Engineer')>QA Engineer</option>
                    <option value="Founder / CTO" @selected(old('role') === 'Founder / CTO')>Founder / CTO</option>
                    <option value="Other" @selected(old('role') === 'Other')>Other</option>
                </select>
            </div>

            <div>
                <label for="company_size" class="block text-sm font-medium text-gray-700 mb-1">Company size</label>
                <select name="company_size" id="company_size" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select company size</option>
                    <option value="1-10" @selected(old('company_size') === '1-10')>1-10 employees</option>
                    <option value="11-50" @selected(old('company_size') === '11-50')>11-50 employees</option>
                    <option value="51-200" @selected(old('company_size') === '51-200')>51-200 employees</option>
                    <option value="201-1000" @selected(old('company_size') === '201-1000')>201-1000 employees</option>
                    <option value="1000+" @selected(old('company_size') === '1000+')>1000+ employees</option>
                </select>
            </div>

            <div>
                <label for="use_case" class="block text-sm font-medium text-gray-700 mb-1">What will you use our platform for?</label>
                <textarea name="use_case" id="use_case" rows="3" required maxlength="1000"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="e.g., Sending transactional emails from our Node.js app">{{ old('use_case') }}</textarea>
            </div>

            <button id="signup-submit" type="submit"
                    class="w-full py-2.5 px-4 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                Sign up
            </button>
        </form>
    </div>

    <script>
        document.getElementById('signup-form').addEventListener('submit', () => {
            const btn = document.getElementById('signup-submit');
            btn.disabled = true;
            btn.textContent = 'Sending...';
        });
    </script>

    @if(session('success'))
        <script>document.getElementById('signup-form').reset();</script>
    @endif
</body>
</html>
