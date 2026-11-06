import type { TimeseriesBucket } from '../types';

export default function ThroughputChart({ data }: { data: TimeseriesBucket[] }) {
  const width = 640;
  const height = 180;
  const padding = 28;
  const max = Math.max(1, ...data.map((b) => b.completed + b.failed));
  const stepX = data.length > 1 ? (width - padding * 2) / (data.length - 1) : 0;

  const points = (key: 'completed' | 'failed') =>
    data
      .map((b, i) => {
        const x = padding + i * stepX;
        const y = height - padding - (b[key] / max) * (height - padding * 2);
        return `${x},${y}`;
      })
      .join(' ');

  return (
    <div className="overflow-x-auto">
      <svg viewBox={`0 0 ${width} ${height}`} className="min-w-[560px] w-full" role="img" aria-label="Completed and failed jobs per hour">
        <line x1={padding} y1={height - padding} x2={width - padding} y2={height - padding} stroke="#e5e7eb" />
        <line x1={padding} y1={padding} x2={padding} y2={height - padding} stroke="#e5e7eb" />
        {data.length > 0 && (
          <>
            <polyline points={points('completed')} fill="none" stroke="#16a34a" strokeWidth="2" />
            <polyline points={points('failed')} fill="none" stroke="#dc2626" strokeWidth="2" />
          </>
        )}
        <text x={width - padding} y={padding - 8} textAnchor="end" fontSize="11" fill="#6b7280">
          Max {max} jobs/hour
        </text>
      </svg>
      <div className="mt-2 flex gap-4 text-xs text-gray-500">
        <span><span className="mr-1 inline-block h-2 w-2 rounded-full bg-green-600" />Completed</span>
        <span><span className="mr-1 inline-block h-2 w-2 rounded-full bg-red-600" />Failed</span>
        <span className="ml-auto">Last {data.length} hours</span>
      </div>
    </div>
  );
}
