<script setup>
    import {ref} from "vue";

    const file_contents = ref('');
    const last_response = ref('');

    function onFileChange(event) {
        const file = event.target.files[0];
        if (file !== undefined) {
            const reader = new FileReader();
            reader.onload = (e) => {
                file_contents.value = e.target.result;
            };
            reader.readAsText(file);
        }
    }

    function sendInventory() {
        const file = document.getElementById('inventory-file').files[0];
        if (file !== undefined) {
            const submitInventory = (token) => {
                const headers = {};
                if (token !== undefined) {
                    headers['Authorization'] = `Bearer ${token}`;
                }
                $.ajax({
                    url: CFG_GLPI['root_doc'] + '/front/inventory.php',
                    method: 'POST',
                    contentType: 'application/json',
                    headers: headers,
                    data: file_contents.value,
                }).then((data) => {
                    alert('Inventory sent');
                    last_response.value = data;
                }, (error) => {
                    alert('Error sending inventory');
                    last_response.value = error.responseText;
                });
            };

            if (document.getElementById('client-id').value === '' || document.getElementById('client-secret').value === '') {
                submitInventory();
            } else {
                // handle OAuth2 if specified
                $.ajax({
                    url: CFG_GLPI['root_doc'] + '/api.php/token',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        grant_type: 'client_credentials',
                        client_id: document.getElementById('client-id').value,
                        client_secret: document.getElementById('client-secret').value,
                        scope: 'inventory'
                    }),
                }).then((data) => {
                    submitInventory(data.access_token);
                }, (error) => {
                    alert('Error getting token');
                    last_response.value = error.responseText;
                });
            }
        }
    }
</script>

<template>
    <div class="py-2 px-3 col-12">
        <div class="d-flex flex-column">
            <div class="form-group row">
                <label class="col-5" for="client-id">Client ID</label>
                <div class="col-7">
                    <div class="input-group">
                        <input type="text" class="form-control" id="client-id">
                    </div>
                </div>
                <label class="col-5" for="client-secret">Client Secret</label>
                <div class="col-7">
                    <div class="input-group">
                        <input type="password" class="form-control" id="client-secret">
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-5" for="inventory-file">Upload inventory file</label>
                <div class="col-7">
                    <div class="input-group">
                        <input type="file" class="form-control" id="inventory-file" @change="onFileChange">
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" @click="sendInventory">Send inventory</button>
                </div>
            </div>
            <div class="form-group row mt-3">
                <label class="col-5" for="last-response">Last response</label>
                <div class="col-7">
                    <div class="input-group">
                        <textarea class="form-control" id="last-response" rows="15" readonly :value="last_response"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
