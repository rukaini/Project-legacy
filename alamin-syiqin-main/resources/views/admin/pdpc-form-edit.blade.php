<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 mb-7">

                <div class="flex items-start gap-4">

                    <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                                flex items-center justify-center shrink-0">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-6 h-6 text-blue-600">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.862 3.487a2.1 2.1 0 013 2.97L8.4 17.92
                                   4 19l1.08-4.4L16.862 3.487z" />

                        </svg>

                    </div>


                    <div>

                        <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                            {{ $form->form_name }}
                        </h1>

                        <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                            Manage Aspect, TUMS, Tahap Tindakan (TT) and Rubric.
                        </p>

                    </div>

                </div>


                <div class="flex flex-wrap items-stretch gap-3">

                    <div class="min-w-[105px] bg-white border border-slate-200
                                rounded-xl px-4 py-3 shadow-sm">

                        <p class="text-[10px] uppercase tracking-[0.06em] text-slate-400 font-bold">
                            Version
                        </p>

                        <p class="text-[18px] font-extrabold text-slate-900 mt-1">
                            {{ $form->version_no }}
                        </p>

                    </div>


                    @if($formUsed)

                    <div class="border border-amber-200 bg-amber-50 rounded-xl px-4 py-3
                                    flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-amber-100
                                        flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-amber-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0
                                           9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-[12px] font-extrabold text-amber-800">
                                Form in use
                            </p>

                            <p class="text-[11px] text-amber-600 mt-0.5">
                                Only wording can be edited
                            </p>

                        </div>

                    </div>

                    @else

                    <div class="border border-blue-200 bg-blue-50 rounded-xl px-4 py-3
                                    flex items-center gap-3">

                        <div class="w-9 h-9 rounded-lg bg-blue-100
                                        flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7" />

                            </svg>

                        </div>

                        <div>

                            <p class="text-[12px] font-extrabold text-blue-800">
                                Not Yet Used
                            </p>

                            <p class="text-[11px] text-blue-600 mt-0.5">
                                Form content can be changed
                            </p>

                        </div>

                    </div>

                    @endif

                </div>

            </div>


            @if(session('success'))

            <div class="mb-5 px-4 py-3 bg-emerald-50 border border-emerald-200
                            text-emerald-700 rounded-xl text-sm font-semibold">
                {{ session('success') }}
            </div>

            @endif


            @if(session('error'))

            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200
                            text-red-700 rounded-xl text-sm font-semibold">
                {{ session('error') }}
            </div>

            @endif


            @if($errors->any())

            <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200
                            text-red-700 rounded-xl">

                <p class="font-semibold mb-2">
                    Form cannot be saved. Please correct the following:
                </p>

                <ul class="list-disc list-inside text-sm space-y-1">

                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

            @endif


            <div x-data="pdpcFormBuilder()">

                <form
                    id="pdpcFormBuilder"
                    method="POST"
                    action="{{ route('admin.pdpc.form.update', $form->formID) }}"
                    class="space-y-7 mb-24">

                    @csrf
                    @method('PUT')


                    {{-- Form Information --}}
                    <section class="app-section-card">

                        <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                Form Information
                            </h2>

                            <p class="text-[12.5px] text-slate-500 mt-1">
                                Update form name and instruction.
                            </p>

                        </div>


                        <div class="p-5 md:p-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <div>

                                    <label class="app-label">
                                        Form Name
                                    </label>

                                    <input
                                        type="text"
                                        name="form_name"
                                        value="{{ old('form_name', $form->form_name) }}"
                                        required
                                        class="app-input">

                                </div>


                                <div>

                                    <label class="app-label">
                                        Instruction
                                    </label>

                                    <input
                                        type="text"
                                        name="instruction"
                                        value="{{ old('instruction', $form->instruction) }}"
                                        class="app-input">

                                </div>

                            </div>

                        </div>

                    </section>


                    {{-- Content Guide --}}
                    <section class="app-section-card">

                        <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                            <div class="flex items-center justify-between gap-4">

                                <div>

                                    <h2 class="text-[17px] font-extrabold text-slate-900">
                                        Content Guide
                                    </h2>

                                    <p class="text-[12.5px] text-slate-500 mt-1">
                                        Form content structure.
                                    </p>

                                </div>


                                @if($formUsed)

                                <span class="app-badge app-badge-warning">
                                    Structure Locked
                                </span>

                                @endif

                            </div>

                        </div>


                        <div class="p-5 md:p-6 grid grid-cols-1 md:grid-cols-4 gap-4">

                            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-4">

                                <p class="font-extrabold text-slate-900 text-[13px]">
                                    1. Aspect
                                </p>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    Main aspect
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-4">

                                <p class="font-extrabold text-slate-900 text-[13px]">
                                    2. TUMS
                                </p>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    TUMS description and wajaran
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-4">

                                <p class="font-extrabold text-slate-900 text-[13px]">
                                    3. Tahap Tindakan (TT)
                                </p>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    One or more TT points
                                </p>

                            </div>


                            <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-4">

                                <p class="font-extrabold text-slate-900 text-[13px]">
                                    4. RTK Rubric
                                </p>

                                <p class="text-[12px] text-slate-500 mt-1">
                                    Rubric points RTK 0 - RTK 4
                                </p>

                            </div>

                        </div>

                    </section>


                    <template x-for="(aspect, aspectIndex) in aspects" :key="aspect._key">

                        <section class="app-section-card">

                            <input
                                type="hidden"
                                :name="`aspects[${aspectIndex}][aspectID]`"
                                :value="aspect.aspectID">


                            {{-- Aspect --}}
                            <div class="px-5 md:px-6 py-5 bg-slate-50 border-b border-slate-200">

                                <div class="flex flex-col md:flex-row md:items-end gap-4">

                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 flex-1">

                                        <div>

                                            <label class="app-label">
                                                Aspect Code
                                            </label>


                                            @if($formUsed)

                                            <input
                                                type="text"
                                                x-model="aspect.aspect_code"
                                                disabled
                                                class="app-input !bg-slate-100 !text-slate-500 cursor-not-allowed">

                                            @else

                                            <input
                                                type="text"
                                                x-model="aspect.aspect_code"
                                                :name="`aspects[${aspectIndex}][aspect_code]`"
                                                placeholder="Example: 4.1"
                                                class="app-input">

                                            @endif

                                        </div>


                                        <div class="md:col-span-3">

                                            <label class="app-label">
                                                Aspect
                                            </label>

                                            <input
                                                type="text"
                                                x-model="aspect.aspect_name"
                                                :name="`aspects[${aspectIndex}][aspect_name]`"
                                                required
                                                placeholder="Enter aspect"
                                                class="app-input">

                                        </div>

                                    </div>


                                    @if(!$formUsed)

                                    <button
                                        type="button"
                                        x-show="aspects.length > 1"
                                        @click="removeAspect(aspectIndex)"
                                        title="Delete Aspect"
                                        class="w-10 h-10 flex-shrink-0 inline-flex items-center justify-center
                                                   bg-red-50 border border-red-200 hover:bg-red-100
                                                   text-red-600 rounded-lg transition">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                        </svg>

                                    </button>

                                    @endif

                                </div>

                            </div>


                            {{-- TUMS --}}
                            <div class="p-5 md:p-6 bg-blue-50/20 space-y-5">

                                <template x-for="(tum, tumIndex) in aspect.tums" :key="tum._key">

                                    <div class="rounded-2xl bg-white border border-slate-200 overflow-hidden">

                                        <input
                                            type="hidden"
                                            :name="`aspects[${aspectIndex}][tums][${tumIndex}][tumsID]`"
                                            :value="tum.tumsID">


                                        <div class="p-5 bg-blue-50/50 border-b border-blue-100">

                                            <div class="grid grid-cols-1 lg:grid-cols-[150px_1fr_140px_42px] gap-4 items-end">

                                                <div>

                                                    <label class="app-label">
                                                        TUMS Code
                                                    </label>


                                                    @if($formUsed)

                                                    <input
                                                        type="text"
                                                        x-model="tum.tums_code"
                                                        disabled
                                                        class="app-input !bg-slate-100 !text-slate-500 cursor-not-allowed">

                                                    @else

                                                    <input
                                                        type="text"
                                                        x-model="tum.tums_code"
                                                        :name="`aspects[${aspectIndex}][tums][${tumIndex}][tums_code]`"
                                                        placeholder="4.1.1"
                                                        class="app-input">

                                                    @endif

                                                </div>


                                                <div>

                                                    <label class="app-label">
                                                        TUMS
                                                    </label>

                                                    <input
                                                        type="text"
                                                        x-model="tum.tums_name"
                                                        :name="`aspects[${aspectIndex}][tums][${tumIndex}][tums_name]`"
                                                        required
                                                        placeholder="Enter TUMS"
                                                        class="app-input">

                                                </div>


                                                <div>

                                                    <label class="app-label">
                                                        Wajaran
                                                    </label>


                                                    @if($formUsed)

                                                    <input
                                                        type="number"
                                                        x-model="tum.wajaran"
                                                        disabled
                                                        class="app-input !bg-slate-100 !text-slate-500 cursor-not-allowed">

                                                    @else

                                                    <input
                                                        type="number"
                                                        x-model="tum.wajaran"
                                                        :name="`aspects[${aspectIndex}][tums][${tumIndex}][wajaran]`"
                                                        required
                                                        min="0"
                                                        max="100"
                                                        step="0.01"
                                                        placeholder="10"
                                                        class="app-input">

                                                    @endif

                                                </div>


                                                <div>

                                                    @if(!$formUsed)

                                                    <button
                                                        type="button"
                                                        x-show="aspect.tums.length > 1"
                                                        @click="removeTum(aspectIndex, tumIndex)"
                                                        title="Delete TUMS"
                                                        class="w-10 h-10 inline-flex items-center justify-center
                                                                   bg-red-50 border border-red-200 hover:bg-red-100
                                                                   text-red-600 rounded-lg">

                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2">

                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                                        </svg>

                                                    </button>

                                                    @endif

                                                </div>

                                            </div>

                                        </div>


                                        <div class="p-5 space-y-5">

                                            <template x-for="(tt, ttIndex) in tum.tt" :key="tt._key">

                                                <div class="rounded-xl bg-slate-50 border border-slate-200 p-5">

                                                    <input
                                                        type="hidden"
                                                        :name="`aspects[${aspectIndex}][tums][${tumIndex}][tt][${ttIndex}][ttID]`"
                                                        :value="tt.ttID">


                                                    <div class="flex items-center justify-between gap-4 mb-4">

                                                        <div>

                                                            <p class="text-[13px] font-extrabold text-slate-800">
                                                                Tahap Tindakan (TT)
                                                            </p>

                                                            <p class="text-[11px] text-slate-400 mt-1">
                                                                Add one or more points
                                                            </p>

                                                        </div>


                                                        @if(!$formUsed)

                                                        <button
                                                            type="button"
                                                            x-show="tum.tt.length > 1"
                                                            @click="removeTt(aspectIndex, tumIndex, ttIndex)"
                                                            title="Delete Tahap Tindakan"
                                                            class="w-9 h-9 inline-flex items-center justify-center
                                                                       bg-red-50 border border-red-200
                                                                       hover:bg-red-100 text-red-600 rounded-lg">

                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                class="w-4 h-4"
                                                                fill="none"
                                                                viewBox="0 0 24 24"
                                                                stroke="currentColor"
                                                                stroke-width="2">

                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                                            </svg>

                                                        </button>

                                                        @endif

                                                    </div>


                                                    <div class="space-y-3">

                                                        <template x-for="(point, pointIndex) in tt.points" :key="point._key">

                                                            <div class="flex items-start gap-3">

                                                                <input
                                                                    type="hidden"
                                                                    :name="`aspects[${aspectIndex}][tums][${tumIndex}][tt][${ttIndex}][points][${pointIndex}][pointID]`"
                                                                    :value="point.pointID">


                                                                <div
                                                                    class="w-8 h-10 flex items-center justify-center
                                                                           text-[12px] font-bold text-slate-400"
                                                                    x-text="pointIndex + 1">
                                                                </div>


                                                                <textarea
                                                                    x-model="point.point_text"
                                                                    :name="`aspects[${aspectIndex}][tums][${tumIndex}][tt][${ttIndex}][points][${pointIndex}][point_text]`"
                                                                    required
                                                                    rows="2"
                                                                    placeholder="Enter TT point"
                                                                    class="app-textarea flex-1"></textarea>


                                                                @if(!$formUsed)

                                                                <button
                                                                    type="button"
                                                                    x-show="tt.points.length > 1"
                                                                    @click="removePoint(aspectIndex, tumIndex, ttIndex, pointIndex)"
                                                                    title="Delete Point"
                                                                    class="w-10 h-10 inline-flex items-center justify-center
                                                                               bg-red-50 border border-red-200
                                                                               hover:bg-red-100 text-red-600 rounded-lg">

                                                                    <svg
                                                                        xmlns="http://www.w3.org/2000/svg"
                                                                        class="w-4 h-4"
                                                                        fill="none"
                                                                        viewBox="0 0 24 24"
                                                                        stroke="currentColor"
                                                                        stroke-width="2">

                                                                        <path
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5" />

                                                                    </svg>

                                                                </button>

                                                                @endif

                                                            </div>

                                                        </template>

                                                    </div>


                                                    @if(!$formUsed)

                                                    <button
                                                        type="button"
                                                        @click="addPoint(aspectIndex, tumIndex, ttIndex)"
                                                        class="mt-4 app-button-outline">

                                                        + Add TT Point

                                                    </button>

                                                    @endif

                                                </div>

                                            </template>


                                            @if(!$formUsed)

                                            <button
                                                type="button"
                                                @click="addTt(aspectIndex, tumIndex)"
                                                class="app-button-outline">

                                                + Add Tahap Tindakan

                                            </button>

                                            @endif


                                            {{-- RTK --}}
                                            <div class="rounded-xl border border-blue-200 overflow-hidden">

                                                <div class="px-5 py-4 bg-blue-50 border-b border-blue-200">

                                                    <div class="flex items-center justify-between gap-4">

                                                        <div>

                                                            <p class="font-extrabold text-blue-900 text-[13px]">
                                                                Rubrik Tahap Kualiti (RTK)
                                                            </p>

                                                            <p class="text-[11px] text-blue-600 mt-1">

                                                                @if($formUsed)
                                                                Only rubric wording can be edited
                                                                @else
                                                                Define the rubric
                                                                @endif

                                                            </p>

                                                        </div>


                                                        <span class="app-badge app-badge-info">
                                                            RTK 0 - RTK 4
                                                        </span>

                                                    </div>

                                                </div>


                                                <div class="p-5 space-y-3 bg-white">

                                                    <template x-for="score in [4, 3, 2, 1, 0]" :key="score">

                                                        <div class="grid grid-cols-[70px_1fr] gap-3 items-start">

                                                            <div
                                                                class="h-10 rounded-lg bg-blue-50 border border-blue-100
                                                                       text-blue-700 flex items-center justify-center
                                                                       text-[11px] font-extrabold"
                                                                x-text="`RTK ${score}`">
                                                            </div>

                                                            <textarea
                                                                x-model="tum.rubrics[score]"
                                                                :name="`aspects[${aspectIndex}][tums][${tumIndex}][rubrics][${score}]`"
                                                                rows="3"
                                                                required
                                                                :placeholder="`Enter rubric for RTK ${score}`"
                                                                class="app-textarea"></textarea>

                                                        </div>

                                                    </template>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </template>


                                @if(!$formUsed)

                                <button
                                    type="button"
                                    @click="addTum(aspectIndex)"
                                    class="app-button-outline">

                                    + Add TUMS

                                </button>

                                @endif

                            </div>

                        </section>

                    </template>


                    @if(!$formUsed)

                    <section class="app-section-card p-5 md:p-6">

                        <button
                            type="button"
                            @click="addAspect()"
                            class="app-button-outline">

                            + Add Aspect

                        </button>

                    </section>

                    @endif

                </form>

            </div>

        </div>

    </div>


    {{-- Bottom actions --}}
    <div class="fixed bottom-4 left-0 right-0 z-40 px-6 pointer-events-none">

        <div class="max-w-7xl mx-auto">

            <div class="bg-white/95 backdrop-blur-md border border-slate-200
                        shadow-xl rounded-2xl px-5 py-4 pointer-events-auto">

                <div class="flex items-center justify-between gap-3">

                    <a
                        href="{{ route('admin.pdpc.form') }}"
                        class="app-button-outline">

                        Back

                    </a>


                    <div class="flex items-center gap-3">

                        <a
                            href="{{ route('admin.pdpc.form.preview', $form->formID) }}"
                            class="app-button-outline">

                            Preview

                        </a>


                        <button
                            type="submit"
                            form="pdpcFormBuilder"
                            class="app-button-primary">

                            Save

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Initial Builder Data --}}
    <script type="application/json" id="pdpc-initial-aspects">
        @json($initialAspects)
    </script>


    {{-- Alpine --}}
    <script>
        document.addEventListener('alpine:init', () => {

            Alpine.data('pdpcFormBuilder', () => ({

                aspects: [],
                nextKey: 0,

                init() {

                    const initial = JSON.parse(
                        document.getElementById('pdpc-initial-aspects').textContent
                    );

                    this.aspects = initial.map(
                        aspect => this.normaliseAspect(aspect)
                    );
                },

                key() {
                    return ++this.nextKey;
                },

                normaliseAspect(aspect = {}) {

                    const tums = aspect.tums?.length ?
                        aspect.tums : [{}];

                    return {
                        _key: this.key(),

                        aspectID: aspect.aspectID ?? '',

                        aspect_code: aspect.aspect_code ?? '',

                        aspect_name: aspect.aspect_name ?? '',

                        tums: tums.map(
                            tum => this.normaliseTum(tum)
                        )
                    };
                },

                normaliseTum(tum = {}) {

                    const tt = tum.tt?.length ?
                        tum.tt : [{}];

                    return {
                        _key: this.key(),

                        tumsID: tum.tumsID ?? '',

                        tums_code: tum.tums_code ?? '',

                        tums_name: tum.tums_name ?? '',

                        wajaran: tum.wajaran ?? '',

                        rubrics: {
                            0: '',
                            1: '',
                            2: '',
                            3: '',
                            4: '',
                            ...(tum.rubrics ?? {})
                        },

                        tt: tt.map(
                            item => this.normaliseTt(item)
                        )
                    };
                },

                normaliseTt(tt = {}) {

                    const points = tt.points?.length ?
                        tt.points : [{}];

                    return {
                        _key: this.key(),

                        ttID: tt.ttID ?? '',

                        points: points.map(
                            point => this.normalisePoint(point)
                        )
                    };
                },

                normalisePoint(point = {}) {

                    return {
                        _key: this.key(),

                        pointID: point.pointID ?? '',

                        point_text: point.point_text ?? ''
                    };
                },

                addAspect() {
                    this.aspects.push(
                        this.normaliseAspect()
                    );
                },

                removeAspect(index) {
                    this.aspects.splice(index, 1);
                },

                addTum(aspectIndex) {
                    this.aspects[aspectIndex]
                        .tums
                        .push(
                            this.normaliseTum()
                        );
                },

                removeTum(aspectIndex, tumIndex) {
                    this.aspects[aspectIndex]
                        .tums
                        .splice(tumIndex, 1);
                },

                addTt(aspectIndex, tumIndex) {
                    this.aspects[aspectIndex]
                        .tums[tumIndex]
                        .tt
                        .push(
                            this.normaliseTt()
                        );
                },

                removeTt(aspectIndex, tumIndex, ttIndex) {
                    this.aspects[aspectIndex]
                        .tums[tumIndex]
                        .tt
                        .splice(ttIndex, 1);
                },

                addPoint(aspectIndex, tumIndex, ttIndex) {
                    this.aspects[aspectIndex]
                        .tums[tumIndex]
                        .tt[ttIndex]
                        .points
                        .push(
                            this.normalisePoint()
                        );
                },

                removePoint(aspectIndex, tumIndex, ttIndex, pointIndex) {
                    this.aspects[aspectIndex]
                        .tums[tumIndex]
                        .tt[ttIndex]
                        .points
                        .splice(pointIndex, 1);
                }

            }));

        });
    </script>

</x-app-layout>