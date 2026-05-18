const products = [
  {
    name: "Casque Bluetooth",
    vendor: "Demo Store",
    price: "299 MAD",
    stock: 12,
    color: "bg-amber-100",
    accent: "bg-amber-500",
  },
  {
    name: "Sac Laptop",
    vendor: "Tech Vendor",
    price: "199 MAD",
    stock: 5,
    color: "bg-cyan-100",
    accent: "bg-cyan-500",
  },
  {
    name: "Montre Smart",
    vendor: "Gadget House",
    price: "449 MAD",
    stock: 3,
    color: "bg-rose-100",
    accent: "bg-rose-500",
  },
];

const vendors = [
  { store: "Demo Store", status: "approved", products: 8 },
  { store: "New Fashion", status: "pending", products: 0 },
  { store: "Gadget House", status: "suspended", products: 14 },
];

const stats = [
  { label: "Total products", value: "12" },
  { label: "Active products", value: "9" },
  { label: "Draft products", value: "3" },
  { label: "Low stock", value: "2" },
];

export default function Home() {
  return (
    <main className="min-h-screen bg-[#f7f5ef] text-zinc-950">
      <header className="border-b border-zinc-200 bg-white">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
          <div>
            <p className="text-sm font-medium text-emerald-700">Marketplace</p>
            <h1 className="text-xl font-bold">Multi-Vendor Workspace</h1>
          </div>
          <nav className="flex flex-wrap gap-3 text-sm font-medium text-zinc-600">
            <a className="hover:text-zinc-950" href="#products">
              Products
            </a>
            <a className="hover:text-zinc-950" href="#vendor">
              Vendor
            </a>
            <a className="hover:text-zinc-950" href="#admin">
              Admin
            </a>
          </nav>
        </div>
      </header>

      <section className="mx-auto grid max-w-6xl gap-6 px-6 py-8 lg:grid-cols-[1.2fr_0.8fr]">
        <div className="rounded-lg border border-zinc-200 bg-white p-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 className="text-3xl font-bold tracking-normal">
                Products, stores, and approvals
              </h2>
              <p className="mt-2 max-w-2xl text-zinc-600">
                Manage vendor products, follow store status, and keep stock visible from one clean dashboard.
              </p>
            </div>
            <button className="rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">
              Add product
            </button>
          </div>
        </div>

        <form className="rounded-lg border border-zinc-200 bg-white p-6">
          <h2 className="text-lg font-semibold">Login</h2>
          <div className="mt-4 grid gap-3">
            <input
              className="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600"
              placeholder="Email"
              type="email"
            />
            <input
              className="rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-emerald-600"
              placeholder="Password"
              type="password"
            />
            <button className="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">
              Sign in
            </button>
          </div>
        </form>
      </section>

      <section id="products" className="mx-auto max-w-6xl px-6 py-6">
        <div className="mb-4 flex items-center justify-between gap-4">
          <h2 className="text-2xl font-semibold">Products</h2>
          <input
            className="w-full max-w-xs rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-600"
            placeholder="Search products"
          />
        </div>
        <div className="grid gap-4 md:grid-cols-3">
          {products.map((product) => (
            <article
              key={product.name}
              className="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm"
            >
              <div className={`h-32 ${product.color} p-4`}>
                <div className={`h-full rounded-md ${product.accent} opacity-80`} />
              </div>
              <div className="p-5">
                <h3 className="font-semibold">{product.name}</h3>
                <p className="text-sm text-zinc-500">{product.vendor}</p>
                <div className="mt-4 flex items-center justify-between">
                  <span className="font-bold">{product.price}</span>
                  <span className="rounded-md bg-emerald-50 px-2 py-1 text-sm text-emerald-700">
                    Stock: {product.stock}
                  </span>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>

      <section id="vendor" className="mx-auto max-w-6xl px-6 py-6">
        <h2 className="mb-4 text-2xl font-semibold">Vendor Dashboard</h2>
        <div className="grid gap-4 md:grid-cols-4">
          {stats.map((stat) => (
            <div key={stat.label} className="rounded-lg border border-zinc-200 bg-white p-5">
              <p className="text-sm text-zinc-500">{stat.label}</p>
              <strong className="mt-2 block text-2xl">{stat.value}</strong>
            </div>
          ))}
        </div>

        <form className="mt-6 rounded-lg border border-zinc-200 bg-white p-5">
          <h3 className="mb-4 font-semibold">Product form</h3>
          <div className="grid gap-3 md:grid-cols-4">
            <input className="rounded-md border border-zinc-300 px-3 py-2 text-sm" placeholder="Product name" />
            <input className="rounded-md border border-zinc-300 px-3 py-2 text-sm" placeholder="Price" />
            <input className="rounded-md border border-zinc-300 px-3 py-2 text-sm" placeholder="Quantity" />
            <select className="rounded-md border border-zinc-300 px-3 py-2 text-sm" defaultValue="active">
              <option value="active">Active</option>
              <option value="draft">Draft</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <button className="mt-4 rounded-md bg-zinc-950 px-4 py-2 text-sm font-semibold text-white">
            Save product
          </button>
        </form>
      </section>

      <section id="admin" className="mx-auto max-w-6xl px-6 py-6 pb-12">
        <h2 className="mb-4 text-2xl font-semibold">Admin Vendors</h2>
        <div className="overflow-hidden rounded-lg border border-zinc-200 bg-white">
          {vendors.map((vendor) => (
            <div
              key={vendor.store}
              className="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 p-4 last:border-b-0"
            >
              <div>
                <h3 className="font-semibold">{vendor.store}</h3>
                <p className="text-sm text-zinc-500">{vendor.products} products</p>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <span className="rounded-md bg-zinc-100 px-3 py-1 text-sm">{vendor.status}</span>
                <button className="rounded-md bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">
                  Approve
                </button>
                <button className="rounded-md bg-red-700 px-3 py-2 text-sm font-semibold text-white">
                  Reject
                </button>
              </div>
            </div>
          ))}
        </div>
      </section>
    </main>
  );
}
