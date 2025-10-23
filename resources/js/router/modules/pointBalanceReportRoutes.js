import PointBalanceReportComponent from "../../components/admin/pointBalanceReport/PointBalanceReportComponent";

export default [
    {
        path: "/admin/point-balance-report",
        component: PointBalanceReportComponent,
        name: "admin.point-balance-report",
        meta: {
            isFrontend: false,
            auth: true,
            permissionUrl: "point-balance-report",
            breadcrumb: "point_balance_report",
        },
    },
];
