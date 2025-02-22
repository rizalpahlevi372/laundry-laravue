<template>
    <div class="row">
        <div class="col-md-6">
            <div
                class="form-group"
                :class="{ 'has-error': errors.customer_id }"
            >
                <label for=""
                    >Customer
                    <sup
                        ><a href="javascript:void(0)" @click="newCustomer"
                            >New Customer</a
                        ></sup
                    ></label
                >
                <!-- KITA AKAN MENGGUNAKAN V-SELECT DIMANA DATANYA AKAN DILOAD KETIKA KEYWORD PENCARIAN DITEMUKAN -->
                <v-select
                    :options="customers.data"
                    v-model="transactions.customer_id"
                    @search="onSearch"
                    label="name"
                    placeholder="Masukkan Kata Kunci"
                    :filterable="false"
                >
                    <template slot="no-options">
                        Masukkan Kata Kunci
                    </template>
                    <template slot="option" slot-scope="option">
                        {{ option.name }}
                    </template>
                </v-select>
                <p class="text-danger" v-if="errors.customer_id">
                    {{ errors.customer_id[0] }}
                </p>
            </div>
        </div>
        <!-- BAGIAN INI AKAN MENAMPILKAN DETAIL CUSTOMER JIKA ISFORM = FALSE, JIKA TRUE, PADA ARTIKEL SELANJUTNYA AKAN DIBUAT FORM UNTUK MENAMBAHKAN CUSTOMER BARU -->
        <div
            class="col-md-6"
            v-if="transactions.customer_id != null && !isForm"
        >
            <table>
                <tr>
                    <th width="30%">NIK</th>
                    <td width="5%">:</td>
                    <td>{{ transactions.customer_id.nik }}</td>
                </tr>
                <tr>
                    <th>No Telp</th>
                    <td>:</td>
                    <td>{{ transactions.customer_id.phone }}</td>
                </tr>
                <tr>
                    <th>Alamat</th>
                    <td>:</td>
                    <td>{{ transactions.customer_id.address }}</td>
                </tr>
                <tr>
                    <th>Deposit</th>
                    <td>:</td>
                    <td>Rp {{ transactions.customer_id.deposit }}</td>
                </tr>
                <tr>
                    <th>Point</th>
                    <td>:</td>
                    <td>{{ transactions.customer_id.point }}</td>
                </tr>
            </table>
        </div>
        <div class="col-md-6" v-if="isForm">
            <h4>Add New Customer</h4>
            <form-customer />
            <button class="btn btn-primary btn-s" @click="addCustomer">
                Save
            </button>
        </div>
        <div class="col-md-12">
            <hr />
            <button
                v-if="filterProduct.length == 0"
                class="btn btn-warning btn-sm"
                style="margin-bottom: 10px"
                @click="addProduct"
            >
                Tambah
            </button>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="40%">Paket</th>
                            <th>Berat/Satuan</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <!-- TABLE INI BERGUNA UNTUK MENAMBAHKAN ITEM TRANSAKSI -->
                    <tbody>
                        <tr
                            v-for="(row, index) in transactions.detail"
                            :key="index"
                        >
                            <td>
                                <v-select
                                    :options="products.data"
                                    v-model="row.laundry_price"
                                    @search="onSearchProduct"
                                    label="name"
                                    placeholder="Masukkan Kata Kunci"
                                    :filterable="false"
                                >
                                    <template slot="no-options">
                                        Masukkan Kata Kunci
                                    </template>
                                    <template slot="option" slot-scope="option">
                                        {{ option.name }}
                                    </template>
                                </v-select>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input
                                        type="number"
                                        v-model="row.qty"
                                        class="form-control"
                                        @blur="calculate(index)"
                                    />
                                    <span class="input-group-addon">{{
                                        row.laundry_price != null &&
                                        row.laundry_price.unit_type ==
                                            "Kilogram"
                                            ? "gram"
                                            : "pcs"
                                    }}</span>
                                </div>
                            </td>
                            <td>Rp {{ row.price }}</td>
                            <td>Rp {{ row.subtotal }}</td>
                            <td>
                                <button
                                    class="btn btn-danger btn-flat"
                                    @click="removeProduct(index)"
                                >
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KETIKA TRANSAKSI BERHASIL, ALERTNYA DITAMPILKAN -->
        <div class="col-md-12" v-if="isSuccess">
            <div class="alert alert-success">
                Transaksi Berhasil, Total Tagihan: Rp {{ total }}
                <strong
                    ><router-link
                        :to="{
                            name: 'transactions.view',
                            params: { id: transaction_id }
                        }"
                        >Lihat Detail</router-link
                    ></strong
                >
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from "vuex";
import FormCustomer from "../customers/Form.vue";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
import _ from "lodash";

export default {
    name: "FormTransaction",
    data() {
        return {
            transaction_id: null,
            isForm: false,
            isSuccess: false,
            transactions: {
                customer_id: null,
                //KITA SET DEFAULT DETAILNYA 1 ITEM YANG KOSONG
                detail: [{ laundry_price: null, qty: 1, price: 0, subtotal: 0 }]
            }
        };
    },
    computed: {
        ...mapState(["errors"]),
        ...mapState("transaction", {
            customers: state => state.customers, //GET STATE CUSTOMER DARI MODULE TRANSACTION
            products: state => state.products //GET STATE PRODUCT DARI MODULE TRANSACTION
        }),
        total() {
            //MENJUMLAH SUBTOTAL
            return _.sumBy(this.transactions.detail, function(o) {
                return parseFloat(o.subtotal);
            });
        },
        filterProduct() {
            return _.filter(this.transactions.detail, function(item) {
                return item.laundry_price == null;
            });
        }
    },
    methods: {
        ...mapActions("transaction", [
            "getCustomers",
            "getProducts",
            "createTransaction"
        ]),
        ...mapActions("customer", ["submitCustomer"]),
        addCustomer() {
            this.submitCustomer().then(res => {
                his.transactions.customer_id = res.data;
                this.isForm = false;
            });
        },
        //METHOD INI AKAN BERJALAN KETIKA PENCARIAN DATA CUSTOMER PADA V-SELECT DIATAS
        onSearch(search, loading) {
            //KITA AKAN ME-REQUEST DATA CUSTOMER BERDASARKAN KEYWORD YG DIMINTA
            this.getCustomers({
                search: search,
                loading: loading
            });
        },
        //METHOD INI UNTUK PENCARIAN DATA PRODUK UNTUK ITEM LAUNDRY
        onSearchProduct(search, loading) {
            //ME-REQUEST DATA PRODUCT
            this.getProducts({
                search: search,
                loading: loading
            });
        },
        //KETIKA TOMBOL TAMBAHKAN DITEKAN, MAKA AKAN MENAMBAHKAN ITEM BARU
        addProduct() {
            if (this.filterProduct.length == 0) {
                this.transactions.detail.push({
                    laundry_price: null,
                    qty: null,
                    price: 0,
                    subtotal: 0
                });
            }
            this.transactions.detail.push({
                laundry_price: null,
                qty: null,
                price: 0,
                subtotal: 0
            });
        },
        //KETIKA TOMBOL HAPUS PADA MASING-MASING ITEM DITEKAN, MAKA AKAN MENGHAPUS BERDASARKAN INDEX DATANYA
        removeProduct(index) {
            if (this.transactions.detail.length > 1) {
                this.transactions.detail.splice(index, 1);
            }
        },
        //KETIKA INPTAN QTY / BERAT /SATUAN UN-FOCUS, MAKA FUNGSI INI AKAN DIJALANKAN
        calculate(index) {
            let data = this.transactions.detail[index];
            if (data.laundry_price != null) {
                //DIMANA KITA AKAN MENGISI PRICE UNTUK SETIAP ITEMNYA DAN PRICENYA DIDAPATKAN DARI DATA PRODUCT LAUNDRY
                data.price = data.laundry_price.price;
                //ADAPUN SUBTOTAL AKAN DIHITUNG BERDASARKAN JENISNYA
                if (data.laundry_price.unit_type == "Kilogram") {
                    //JIKA KILOGRAM MAKA BERAT BARANG * HARGA /1000
                    data.subtotal = (
                        parseInt(data.laundry_price.price) *
                        (parseInt(data.qty) / parseInt(1000))
                    ).toFixed(2);
                } else {
                    //JIKA SATUAN, MAKA HARGA * QTY
                    data.subtotal =
                        parseInt(data.laundry_price.price) * parseInt(data.qty);
                }
            }
        },
        //KETIKA TOMBOL CREATE TRANSACTION DITEKAN MAKA FUNGSI INI AKAN DIJALANKAN
        submit() {
            this.isSuccess = false;
            let filter = _.filter(this.transactions.detail, function(item) {
                return item.laundry_price != null;
            });
            if (filter.length > 0) {
                this.createTransaction(this.transactions).then(res => {
                    this.transaction_id = res.data.id;
                    this.isSuccess = true;
                });
            }
            this.createTransaction(this.transactions).then(
                () => (this.isSuccess = true)
            );
        },
        newCustomer() {
            this.isForm = true; //MENGUBAH VALUE isForm MENJADI TRUE
        },
        resetForm() {
            this.transactions = {
                customer_id: null,
                detail: [{ laundry_price: null, qty: 1, price: 0, subtotal: 0 }]
            };
        }
    },
    components: {
        vSelect,
        "form-customer": FormCustomer
    }
};
</script>
