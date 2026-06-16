<x-guest-layout>
<div class="max-w-7xl mx-auto py-10 px-4">

    <h1
        class="text-4xl
        font-bold
        text-center
        mb-10"
    >
        Contact Us
    </h1>

    <div
        class="grid
        md:grid-cols-2
        gap-10"
    >

        {{-- LEFT --}}
        <div>

            <h2
                class="text-2xl
                font-bold
                mb-6"
            >
                Get In Touch
            </h2>

            <div class="space-y-5">

                <div>
                    📍 Cape Coast, Ghana
                </div>

                <div>
                    📞 +233 55 123 4567
                </div>

                <div>
                    ✉️ info@myhotel.com
                </div>
                <div>
                    🕒 Open 24/7
                </div>
            </div>

            {{-- MAP --}}
            <div class="mt-8">
                <iframe
                    src="https://maps.google.com/maps?q=Cape%20Coast&t=&z=13&ie=UTF8&iwloc=&output=embed"
                    class="w-full h-80 rounded-xl"
                    loading="lazy"
                ></iframe>
            </div>
        </div>

        {{-- RIGHT --}}
        <div
            class="bg-white
            rounded-2xl
            shadow-lg
            p-8"
        >

            <h2
                class="text-2xl
                font-bold
                mb-6"
            >
                Send a Message
            </h2>

            @if(session('success'))
                <div
                    class="bg-green-100
                    text-green-700
                    p-4
                    rounded-lg
                    mb-5"
                >
                    {{
                        session('success')
                    }}
                </div>
            @endif

            <form
                method="POST"
                action="/contact"
            >

                @csrf

                <input
                    type="text"
                    name="name"
                    placeholder="Your Name"
                    class="w-full
                    border
                    rounded-lg
                    p-3 mb-4"
                    required
                >

                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    class="w-full
                    border
                    rounded-lg
                    p-3 mb-4"
                    required
                >

                <input
                    type="text"
                    name="phone_number"
                    placeholder="Phone Number"
                    class="w-full
                    border
                    rounded-lg
                    p-3 mb-4"
                >

                <input
                    type="text"
                    name="subject"
                    placeholder="Subject"
                    class="w-full
                    border
                    rounded-lg
                    p-3 mb-4"
                    required
                >

                <textarea
                    name="message"
                    rows="6"
                    placeholder="Message"
                    class="w-full
                    border
                    rounded-lg
                    p-3 mb-4"
                    required
                ></textarea>

                <button
                    class="w-full
                    bg-blue-600
                    text-white
                    py-3
                    rounded-lg
                    hover:bg-blue-700"
                >
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>
</x-guest-layout>