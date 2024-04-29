<script setup>
import axios from 'axios';
import { reactive, onMounted, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useToastr } from '@/toastr';
import { Form } from 'vee-validate';
import flatpickr from "flatpickr";
import 'flatpickr/dist/themes/light.css';


const errors = ref({});
const router = useRouter();
const route = useRoute();
const toastr = useToastr();

const form = reactive({
    start_time: '',
    description: '',
    requestor_name: '',
    exit_pass_code: '',
    transfer_location_from: '',
    transfer_location_to: '',
    item_condition: '',
    incharge_sig: null,
    finance_sig: null,
    receive_sig: null,
    signature: null,
    signature_path: '',
    third_parties: '',
    approved_letter_path: '',
    po_path: '',
    or_path: '',
    verified_by: '',
    approved_by: '',
    incharge_sig_path: '',
    finance_sig_path: '',
    received_by: '',
    receive_sig_path: '',
    
});

const handleFileUpload = (event, fileType) => {
    const file = event.target.files[0];
    // Check if the file is an image (JPEG or PNG)
    if (!['image/jpeg', 'image/png'].includes(file.type)) {
        toastr.error('The file must be an image (JPEG, PNG).');
        return;
    }
    // Check file size (2MB limit)
    if (file.size > 2048 * 1024) {
        toastr.error('The file size must be less than 2MB.');
        return;
    }
    form[fileType] = file;
};
const handleSignatureUpload = (event) => {
    form.signature = event.target.files[0];
};
const handleInchargeSigUpload = (event) => {
    form.incharge_sig = event.target.files[0];
};

const handleFinanceSigUpload = (event) => {
    form.finance_sig = event.target.files[0];
};

const handleReceiveSigUpload = (event) => {
    form.receive_sig = event.target.files[0];
};

const handleSubmit = async (values, errors) => {
    if (editMode.value) {
        await editAppointment(values, errors);
    } else {
        await createAppointment(values, errors);
    }
};

const createAppointment = async (values, actions) => {
    try {
        const formData = new FormData();
        Object.entries(values).forEach(([key, value]) => {
            // Exclude the signature field from formData
            if (key !== 'incharge_sig' && key !== 'finance_sig' && key !== 'receive_sig') {
                formData.append(key, value);
            }
        });
        
        // Append the signature field
       
        
        // Append other signature fields if they exist
        if (values.incharge_sig) {
            formData.append('incharge_sig', values.incharge_sig);
        }
        if (values.finance_sig) {
            formData.append('finance_sig', values.finance_sig);
        }
        if (values.receive_sig) {
            formData.append('receive_sig', values.receive_sig);
        }if (values.signature) {
            formData.append('signature', values.signature);
        }
        
        // Generate a random exit passcode
        const exitPassCode = Math.random().toString(36).substring(2, 8).toUpperCase();
        // Assign the generated exit passcode to the form
        formData.append('exit_pass_code', exitPassCode);
        
        await axios.post('/api/appointments/create', formData);
        router.push('/admin/appointments');
        toastr.success('Appointment created successfully!');
    } catch (error) {
        actions.setErrors(error.response.data.errors);
    }
};

const editAppointment = (values, actions,) => {
    const { signature, incharge_sig, finance_sig, receive_sig, ...formData } = values; // Exclude signatures from formData
    
    
    axios.put(`/api/appointments/${route.params.id}/edit`, formData)
    .then((response) => {
        router.push('/admin/appointments');
        toastr.success('Appointment updated successfully!');
    })
    .catch((error) => {
        actions.setErrors(error.response.data.errors);
    })
};


const getAppointment = () => {
    axios.get(`/api/appointments/${route.params.id}/edit`)
    .then(({data}) => {
        form.requestor_name = data.requestor_name;
        form.start_time = data.formatted_start_time;
        form.description = data.description;
        form.transfer_location_from = data.transfer_location_from;
        form.transfer_location_to = data.transfer_location_to;
        form.item_condition = data.item_condition;
        form.signature_path = data.signature_path;
        form.incharge_sig_path = data.incharge_sig_path;
        form.finance_sig_path = data.finance_sig_path;
        form.receive_sig_path = data.receive_sig_path;
        
        // Set signature file object from the signature path
        if (data.signature_path) {
            const signatureFilename = data.signature_path.split('/').pop();
            const signatureFile = new File([], signatureFilename);
            form.signature = signatureFile;
        }
        
        // Set incharge signature file object from the incharge signature path
        if (data.incharge_sig_path) {
            const inchargeSigFilename = data.incharge_sig_path.split('/').pop();
            const inchargeSigFile = new File([], inchargeSigFilename);
            form.incharge_sig = inchargeSigFile;
        }

        // Set finance signature file object from the finance signature path
        if (data.finance_sig_path) {
            const financeSigFilename = data.finance_sig_path.split('/').pop();
            const financeSigFile = new File([], financeSigFilename);
            form.finance_sig = financeSigFile;
        }

        // Set receive signature file object from the receive signature path
        if (data.receive_sig_path) {
            const receiveSigFilename = data.receive_sig_path.split('/').pop();
            const receiveSigFile = new File([], receiveSigFilename);
            form.receive_sig = receiveSigFile;
        }
        
        // Only set exit_pass_code if it exists in the response
        if (data.exit_pass_code) {
            form.exit_pass_code = data.exit_pass_code;
        }
    })
    .catch(error => {
        console.error("Error fetching appointment:", error);
    });
};

const editMode = ref(false);

onMounted(() => {
    if (route.name === 'admin.appointments.edit') {
        editMode.value = true;
        getAppointment();
    }
    
    flatpickr(".flatpickr", {
        enableTime: true,
        dateFormat: "Y-m-d h:i K",
        defaultHour: 10,
    });
});
</script>   

<template>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <span v-if="editMode">View</span>
                    <span v-else>Create</span>
                    Appointment
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <router-link to="/admin/dashboard">Home</router-link>
                    </li>
                    <li class="breadcrumb-item">
                        <router-link to="/admin/appointments">Appointments</router-link>
                    </li>
                    <li class="breadcrumb-item active">
                        <span v-if="editMode">Edit</span>
                        <span v-else>Create</span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <Form @submit="handleSubmit({ ...form }, errors)" v-slot:default="{ errors }">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="requestor_name">Requestor Name</label>
                                        <input v-model="form.requestor_name" type="text" class="form-control" :class="{ 'is-invalid': errors.requestor_name }" id="requestor_name" placeholder="Enter Name">
                                        <span class="invalid-feedback">{{ errors.requestor_name }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="item_condition">Item Status</label>
                                        <input v-model="form.item_condition" type="text" class="form-control" :class="{ 'is-invalid': errors.item_condition }" id="item_condition" placeholder="Enter Item Status">
                                        <span class="invalid-feedback">{{ errors.item_condition }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transfer_location_from">Transfer From</label>
                                        <input v-model="form.transfer_location_from" type="text" class="form-control" :class="{ 'is-invalid': errors.transfer_location_from }" id="transfer_location_from" placeholder="Transfer From">
                                        <span class="invalid-feedback">{{ errors.transfer_location_from }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="transfer_location_to">Transfer To</label>
                                        <input v-model="form.transfer_location_to" type="text" class="form-control" :class="{ 'is-invalid': errors.transfer_location_to }" id="transfer_location_to" placeholder="Transfer To">
                                        <span class="invalid-feedback">{{ errors.transfer_location_to }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start-time">Start Time</label>
                                        <input v-model="form.start_time" type="text" class="form-control flatpickr" :class="{'is-invalid': errors.start_time}" id="start-time">
                                        <span class="invalid-feedback">{{ errors.start_time }}</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="description">Description</label>
                                    <textarea v-model="form.description" class="form-control" :class="{'is-invalid': errors.description}" id="description" rows="3" placeholder="Enter Description"></textarea>
                                    <span class="invalid-feedback">{{ errors.description }}</span>
                                </div>
                            </div> 
                            <div class="row ">
                                <div class="form-group col-md-6" >
                                    <label for="exampleInputFile">Requestor's Signature</label>
                                    <div class="input-group">
                                        <input type="file" class="custom-file-input" id="exampleInputFile" name="signature" @change="event => handleSignatureUpload(event, 'signature')">
                                        <label class="custom-file-label" for="exampleInputFile">{{ form.signature ? form.signature.name : 'Choose file' }}</label>
                                    </div>
                                    <div class="btn-group btn-group-md d-flex">
                                        <a :href="'/storage/' + form.signature_path" target="_blank" class="btn btn-info">
                                            <i class="fas fa-eye"></i> View Signature
                                        </a>
                                    </div>
                                </div> 
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start-time">Transfer to 3rd Parties</label>
                                        <select v-model="form.third_parties" class="custom-select rounded-0" id="exampleSelectRounded0">
                                            <option>Sales</option>
                                            <option>Property Donation</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                            </div> 
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Attachments Required</h3>
                                </div>
                                <div class="form-group ml-3 mt-3">
                                    <label for="approved_letter_path">Approved Letter Request</label>
                                    <div class="input-group">
                                        <input type="file" class="custom-file-input" id="approved_letter_path" name="approved_letter_path" @change="event => handleFileUpload(event, 'approved_letter_path')">
                                        <label class="custom-file-label" for="approved_letter_path">{{ form.approved_letter_path ? form.approved_letter_path.name : 'Choose file' }}</label>
                                    </div>
                                    <div class="btn-group btn-group-md  col-md-2 d-flex">
                                        <a :href="'/storage/' + form.approved_letter_path" target="_blank" class="btn btn-info">
                                            <i class="fas fa-eye "></i>View Attachments
                                        </a>
                                    </div>
                                </div> 
                                <div class="form-group ml-3 mt-3">
                                    <label for="po_path">PO</label>
                                    <div class="input-group">
                                        <input type="file" class="custom-file-input" id="po_path" name="po_path" @change="event => handleFileUpload(event, 'po_path')">
                                        <label class="custom-file-label" for="po_path">{{ form.po_path ? form.po_path.name : 'Choose file' }}</label>
                                    </div>
                                    <div class="btn-group btn-group-md  col-md-2 d-flex">
                                        <a :href="'/storage/' + form.po_path" target="_blank" class="btn btn-info">
                                            <i class="fas fa-eye"></i>View Attachments
                                        </a>
                                    </div>
                                </div> 
                                <div class="form-group ml-3 mt-3">
                                    <label for="or_path">XU Official Receipt</label>
                                    <div class="input-group">
                                        <input type="file" class="custom-file-input" id="or_path" name="or_path" @change="event => handleFileUpload(event, 'or_path')">
                                        <label class="custom-file-label" for="or_path">{{ form.or_path ? form.or_path.name : 'Choose file' }}</label>
                                    </div>
                                    <div class="btn-group btn-group-md  col-md-2 d-flex">
                                        <a :href="'/storage/' + form.or_path" target="_blank" class="btn btn-info">
                                            <i class="fas fa-eye"></i>View Attachments
                                        </a>
                                    </div>
                                </div>                   
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="verified_by">Verified By</label>
                                        <input v-model="form.verified_by" type="text" class="form-control" :class="{ 'is-invalid': errors.verified_by }" id="verified_by" placeholder="Enter Name">
                                        <span class="invalid-feedback">{{ errors.verified_by }}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="approved_by">Approved By</label>
                                        <input v-model="form.approved_by" type="text" class="form-control" :class="{ 'is-invalid': errors.approved_by }" id="approved_by" placeholder="Enter Name">
                                        <span class="invalid-feedback">{{ errors.approved_by }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="form-group col-md-6" >
    <label for="incharge_sig">PPO Incharge/Unit Head Signature</label>
    <div class="input-group">
        <input type="file" class="custom-file-input" id="incharge_sig" name="incharge_sig" @change="event => handleInchargeSigUpload (event, 'incharge_sig')">
        <label class="custom-file-label" for="incharge_sig">{{ form.incharge_sig ? form.incharge_sig.name : 'Choose file' }}</label>
    </div>
    <div class="btn-group btn-group-md d-flex">
        <a :href="'/storage/' + form.incharge_sig_path" target="_blank" class="btn btn-info">
            <i class="fas fa-eye"></i> View Signature
        </a>
    </div>
</div>  
                                <div class="form-group col-md-6">
    <label for="finance_sig">Finance - Director, Asset Mgt</label>
    <div class="input-group">
        <input type="file" class="custom-file-input" id="finance_sig" name="finance_sig" @change="event => handleFinanceSigUpload(event, 'finance_sig')">
        <label class="custom-file-label" for="finance_sig">{{ form.finance_sig ? form.finance_sig.name : 'Choose file' }}</label>
    </div>
    <div class="btn-group btn-group-md d-flex">
        <a :href="'/storage/' + form.finance_sig_path" target="_blank" class="btn btn-info">
            <i class="fas fa-eye"></i> View Signature
        </a>
    </div>
</div>   
                            </div>
                            <div class=" mt-30" style="margin-top: 60px;margin-bottom: 50px;">
                                <div class="col-md-6 ">
                                    <div class="form-group col-mt-10">
                                        <label for="received_by">Received By</label>
                                        <input v-model="form.received_by" type="text" class="form-control" :class="{ 'is-invalid': errors.received_by }" id="received_by" placeholder="Enter Name">
                                        <span class="invalid-feedback">{{ errors.received_by }}</span>
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
    <label for="receive_sig">Item Received By:</label>
    <div class="input-group">
        <input type="file" class="custom-file-input" id="receive_sig" name="receive_sig" @change="event => handleReceiveSigUpload(event, 'receive_sig')">
        <label class="custom-file-label" for="receive_sig">{{ form.receive_sig ? form.receive_sig.name : 'Choose file' }}</label>
    </div>
    <div class="btn-group btn-group-md d-flex">
        <a :href="'/storage/' + form.receive_sig_path" target="_blank" class="btn btn-info">
            <i class="fas fa-eye"></i> Receiver Signature
        </a>
    </div>
</div>  
                            </div>  
                            
                            <div class="row mt-3">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </Form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</template>
