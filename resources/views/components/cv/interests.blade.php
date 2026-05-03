<div class="mt-12 flex flex-wrap gap-2 pt-6 border-t border-gray-100">
    <template x-for="interest in interests" :key="interest.id">
        <div>
            <template x-if="editingItem.id !== interest.id || editingItem.type !== 'interest'">
                <span @dblclick="startEditing('interest', interest)"
                      class="text-[9px] font-bold text-gray-400 uppercase tracking-wider px-3 py-1 bg-gray-50 rounded-full border border-gray-100 cursor-pointer hover:border-indigo-300" 
                      :class="interest.status === 'draft' ? 'border-amber-200 bg-amber-50' : ''"
                      x-text="interest.name"></span>
            </template>
            <template x-if="editingItem.id === interest.id && editingItem.type === 'interest'">
                <input type="text" x-model="editingData.name" @keyup.enter="saveManualEdit()" @blur="saveManualEdit()"
                       class="text-[9px] font-bold uppercase border-indigo-300 rounded-full px-3 py-1 w-24">
            </template>
        </div>
    </template>
</div>
