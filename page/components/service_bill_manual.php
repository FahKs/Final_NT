<div id="manualModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4 mt-20">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h2 class="text-2xl font-bold text-gray-800">คู่มือการใช้งานระบบ</h2>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto flex-1 p-6">
            <div class="space-y-8">
                <!-- Page 1 -->
                <div class="manual-page block" id="page1">
                    <div class="prose max-w-none">
                        <h3 class="text-2xl font-bold text-blue-600 mb-4">หน้าที่ 1: ภาพรวมของระบบ</h3>
                        <p class="text-gray-700 text-gl leading-relaxed">
                        ในหน้ารายละเอียดของบิล พนักงานสามารถเพิ่มข้อมูลหมายเลขบริการ อุปกรณ์ และสร้างกลุ่มอุปกรณ์ไว้จัดเก็บให้เป็นระเบียบได้
                        </p>
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <img src="../img/service_bill/SB0.png" alt="ภาพรวมระบบ" class="w-full rounded-lg border border-gray-200 mb-4">
                            
                            <div class="grid md:grid-cols-1 gap-4">
                                <div class="bg-white p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-semibold text-gray-800 mb-2">ส่วนประกอบหลัก:</h4>
                                    <ul class="list-disc pl-5 ">
                                        <li><strong>การเพิ่มข้อมูลหมายเลขบริการของบิลที่เลือกไว้</strong>
                                            <ul class="list-disc pl-5 ">
                                                <li>พนักงานเพิ่มข้อมูลหมายเลขบริการและเลือกประเภทบริการ ประเภทอุปกรณ์ และสถานะของหมายเลขบริการ</li>
                                            </ul>
                                        <li><strong>การเพิ่มข้อมูลอุปกรณ์</strong> 
                                        <ul class="list-disc pl-5 ">
                                            <li>พนักงานเพิ่มข้อมูลอุปกรณ์ จำนวนอุปกรณ์ และสามารถลงวันที่ติดตั้งของอุปกรณ์ได้</li>
                                        </ul>
                                        <li><strong>การสร้างกลุ่มอุปกรณ์</strong> 
                                        <ul class="list-disc pl-5 ">
                                            <li>พนักงานจัดการสร้างอุปกรณ์โดยสามารถเลือกเฉพาะอุปกรณ์ที่ต้องจัดกลุ่มหรือจะเลือกทั้งหมดก็ได้</li>
                                        </ul>
                                </div>
                                <img src="../img/service_bill/SB1.png" alt="ภาพการลบข้อมูลหมมายเลขบริการ" class="w-full rounded-lg border border-gray-200 mb-4">
                                <img src="../img/service_bill/SB2.png" alt="ภาพการลบข้อมูลบิล" class="w-full rounded-lg border border-gray-200 mb-4">
                                <!-- เพิ่ม div ข้อควรระวังที่นี่ -->
                            <div class="bg-yellow-100 p-4 rounded-lg border-l-4 border-yellow-500 mt-6">
                                <h4 class="text-lg font-bold text-yellow-700 flex items-center">
                                    <svg class="w-6 h-6 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.766-1.36 2.72-1.36 3.486 0l6.518 11.57c.746 1.325-.187 2.981-1.743 2.981H3.482c-1.556 0-2.489-1.656-1.743-2.981l6.518-11.57zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 001-1V7a1 1 0 00-2 0v4a1 1 0 001 1z" clip-rule="evenodd"></path>
                                    </svg>
                                    ข้อควรระวัง
                                </h4>
                                <ul class="list-disc pl-8 text-gray-700 mt-2">
                                    <li>ข้อมูลหมายเลขบริการที่ลบได้จะต้องเป็นข้อมูลที่ไม่มีข้อมูลรายละเอียดแพ็กเกจ</li>
                                    <li>หากอยากลบข้อมูลแต่ไม่สามารถลบได้เหมือนได้รูปให้พนักงานกลับไปลบข้อมูลในหน้าแพ็กเกจให้เสร็จสิ้นก่อน</li>
                                    <li>หากอยากลบข้อมูลอุปกรณ์ที่ต้องการแต่ได้สร้างกลุ่มอุปกรณ์ตัวนั้นแล้วจะไม่สามารถลบได้</li>
                                    <li>ดังนั้นถ้าต้องการลบอุปกรณ์ให้ตรวจเช็คในหน้าการสร้างกลุ่มอุปกรณ์ก่อน หากพบเจอให้พนักงานทำการลบอุปกรณ์ในการสร้างกลุ่มให้เรียบร้อยก่อนแล้วค่อยกลับไปลบอุปกรณ์ที่หน้าการเพิ่มอุปกรณ์อีกครั้ง</li>
                                </ul>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Page 2 -->
            <div class="manual-page block" id="page2">
                    <h3 class="text-2xl font-bold text-blue-600 mb-4">หน้าที่ 2: การแสดงข้อมูลหน้าการเพิ่มหมายเลขบริการ</h3>
                    <p class="text-gray-700 text-xl font-bold mt-2">2.1.การแสดงข้อมูลหน้าการเพิ่มรายละเอียดบริการและการเลือกประเภทต่างๆ</p>
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถเลือกประเภทบริการได้ดังนี้</p>
                    <img src="../img/service_bill/SB3.png" alt="การเลือกประเภทบริการ" class="w-full rounded-lg border border-gray-200 mb-4">
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถเลือกประเภทอุปกรณ์ได้ดังนี้</p>
                    <img src="../img/service_bill/SB4.png" alt="การเลือกประเภทอุปกรณ์" class="w-full rounded-lg border border-gray-200 mb-4">
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถเลือกสถานะได้ดังนี้</p>
                    <img src="../img/service_bill/SB5.png" alt="การเลือกประเภทสถานะ" class="w-full rounded-lg border border-gray-200 mb-4">
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถแก้ไขหมายเลขบริการผ่านไอคอนดินสอสีเหลือง</p>
                    <img src="../img/service_bill/SB6.png" alt="การเลือกแก้ไขข้อมูลบริการ" class="w-full rounded-lg border border-gray-200 mb-4">
                    <div class="prose max-w-none">
                    <p class="text-gray-700 text-xl font-bold mt-2">2.2. การแสดงข้อมูลหน้าการเพิ่มอุปกรณ์</p>
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถเพิ่มข้อมูลอุปกรณ์ จำนวนและวันที่ติดตั้งอุปกรณ์ในบิลที่เลือกไว้ได้</p>
                    <img src="../img/service_bill/SB7.png" alt="การเพิ่มข้อมูลอุปกรณ์บิล" class="w-full rounded-lg border border-gray-200 mb-4">
                    </div>
                    <div class="prose max-w-none">
                    <p class="text-gray-700 text-xl font-bold mt-2">2.3. การแสดงข้อมูลหน้าการสร้างกลุ่มอุปกรณ์</p>
                    <p class="text-gray-700 text-gl leading-relaxed">พนักงานสามารถสร้างกลุ่มอุปกรณ์และจัดกลุ่มให้เป็นระเบียบได้ดังนี้</p>
                    <img src="../img/service_bill/SB8.png" alt="การสร้างกลุ่มอุปกรณ์" class="w-full rounded-lg border border-gray-200 mb-4">
                </div>  
            </div>
</div>
          

        <!-- Navigation -->
        <div class="border-t px-6 py-4 bg-gray-50">
            <div class="flex justify-between items-center">
                <button onclick="prevPage()" class="px-5 py-2.5 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    ก่อนหน้า
                </button>
                
                <div class="flex items-center space-x-2">
                    <span class="text-gray-600">หน้า</span>
                    <span id="pageNumber" class="font-semibold text-blue-600 w-12 text-center">1 / 5</span>
                </div>

                <button onclick="nextPage()" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors flex items-center">
                    ถัดไป
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Custom scrollbar */
    #manualModal ::-webkit-scrollbar {
        width: 8px;
    }
    #manualModal ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    #manualModal ::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }
    #manualModal ::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }

    /* Warning box */
    .warning-box {
        @apply bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4;
    }
</style>